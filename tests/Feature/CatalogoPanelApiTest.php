<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** CU3 por la API del panel: catálogo académico y oferta comercial. */
class CatalogoPanelApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['rol' => Rol::Administrador, 'estado' => 'activo']);
    }

    private function programa(array $extra = []): Programa
    {
        return Programa::create([
            'codigo' => 'PRG-'.fake()->unique()->numerify('###'),
            'nombre' => 'Programa '.fake()->unique()->word(),
            'version' => '1.0',
            'estado_comercial' => 'borrador',
            ...$extra,
        ]);
    }

    private function modulo(array $extra = []): Modulo
    {
        return Modulo::create([
            'codigo' => 'MOD-'.fake()->unique()->numerify('###'),
            'nombre' => 'Módulo '.fake()->unique()->word(),
            'estado_comercial' => 'borrador',
            ...$extra,
        ]);
    }

    public function test_el_listado_de_programas_filtra_por_estado_y_texto(): void
    {
        $this->programa(['nombre' => 'Data Analysis Expert', 'estado_comercial' => 'abierto']);
        $this->programa(['nombre' => 'Ciberseguridad', 'estado_comercial' => 'borrador']);

        $admin = $this->admin();

        $this->actingAs($admin)->getJson('/api/panel/catalogo/programas')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->actingAs($admin)->getJson('/api/panel/catalogo/programas?estado=abierto')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'Data Analysis Expert');

        $this->actingAs($admin)->getJson('/api/panel/catalogo/programas?q=Ciber')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.estado_label', 'Borrador');
    }

    public function test_crear_un_programa_valida_en_el_servidor(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->postJson('/api/panel/catalogo/programas', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['codigo', 'nombre', 'version', 'estado_comercial']);

        $this->actingAs($admin)->postJson('/api/panel/catalogo/programas', [
            'codigo' => 'DATA-01',
            'nombre' => 'Data Analysis Expert',
            'version' => '1.0',
            'estado_comercial' => 'borrador',
        ])->assertCreated()->assertJsonPath('programa.codigo', 'DATA-01');
    }

    public function test_no_se_repite_el_codigo_de_programa(): void
    {
        $this->programa(['codigo' => 'DATA-01']);

        $this->actingAs($this->admin())->postJson('/api/panel/catalogo/programas', [
            'codigo' => 'DATA-01',
            'nombre' => 'Otro programa',
            'version' => '1.0',
            'estado_comercial' => 'borrador',
        ])->assertStatus(422)->assertJsonValidationErrors(['codigo']);
    }

    public function test_el_plan_de_estudio_se_arma_quita_y_reordena(): void
    {
        $admin = $this->admin();
        $programa = $this->programa();
        $uno = $this->modulo(['nombre' => 'Fundamentos']);
        $dos = $this->modulo(['nombre' => 'Avanzado']);

        foreach ([$uno, $dos] as $modulo) {
            $this->actingAs($admin)
                ->postJson("/api/panel/catalogo/programas/{$programa->id}/modulos", ['modulo_id' => $modulo->id])
                ->assertOk();
        }

        $respuesta = $this->actingAs($admin)
            ->getJson("/api/panel/catalogo/programas/{$programa->id}")
            ->assertOk();

        $this->assertSame(['Fundamentos', 'Avanzado'], array_column($respuesta->json('data.modulos'), 'nombre'));

        // El segundo sube al primer lugar del plan.
        $this->actingAs($admin)
            ->patchJson("/api/panel/catalogo/programas/{$programa->id}/modulos/{$dos->id}/mover", ['direccion' => 'subir'])
            ->assertOk();

        $reordenado = $this->actingAs($admin)
            ->getJson("/api/panel/catalogo/programas/{$programa->id}")
            ->assertOk();

        $this->assertSame(['Avanzado', 'Fundamentos'], array_column($reordenado->json('data.modulos'), 'nombre'));

        $this->actingAs($admin)
            ->deleteJson("/api/panel/catalogo/programas/{$programa->id}/modulos/{$uno->id}")
            ->assertOk();

        $this->assertDatabaseMissing('programa_modulo', [
            'programa_id' => $programa->id,
            'modulo_id' => $uno->id,
        ]);
    }

    public function test_un_modulo_puede_pertenecer_a_varios_programas(): void
    {
        $admin = $this->admin();
        $modulo = $this->modulo();
        $uno = $this->programa();
        $dos = $this->programa();

        foreach ([$uno, $dos] as $programa) {
            $this->actingAs($admin)
                ->postJson("/api/panel/catalogo/programas/{$programa->id}/modulos", ['modulo_id' => $modulo->id])
                ->assertOk();
        }

        $this->actingAs($admin)->getJson("/api/panel/catalogo/modulos/{$modulo->id}")->assertOk();

        $this->assertSame(2, $modulo->programas()->count());
    }

    public function test_las_opciones_traen_los_modulos_que_faltan_en_el_programa(): void
    {
        $admin = $this->admin();
        $programa = $this->programa();
        $dentro = $this->modulo(['nombre' => 'Ya incluido']);
        $this->modulo(['nombre' => 'Todavía libre']);

        $this->actingAs($admin)
            ->postJson("/api/panel/catalogo/programas/{$programa->id}/modulos", ['modulo_id' => $dentro->id])
            ->assertOk();

        $respuesta = $this->actingAs($admin)
            ->getJson("/api/panel/catalogo/opciones?programa={$programa->id}")
            ->assertOk();

        $nombres = array_column($respuesta->json('modulos_disponibles'), 'nombre');

        $this->assertContains('Todavía libre', $nombres);
        $this->assertNotContains('Ya incluido', $nombres);
    }

    public function test_un_estudiante_consulta_el_catalogo_pero_no_lo_edita(): void
    {
        $estudiante = User::factory()->create(['rol' => Rol::Estudiante, 'estado' => 'activo']);
        $this->programa();

        $this->actingAs($estudiante)->getJson('/api/panel/catalogo/programas')->assertOk();

        $this->actingAs($estudiante)->postJson('/api/panel/catalogo/programas', [
            'codigo' => 'X-1', 'nombre' => 'No debería', 'version' => '1.0', 'estado_comercial' => 'borrador',
        ])->assertForbidden();
    }

    public function test_crear_y_editar_un_modulo(): void
    {
        $admin = $this->admin();

        $respuesta = $this->actingAs($admin)->postJson('/api/panel/catalogo/modulos', [
            'codigo' => 'MOD-99',
            'nombre' => 'Lógica de programación',
            'estado_comercial' => 'abierto',
            'duracion_horas' => 40,
            'prerrequisito' => 'Nociones básicas de computación.',
        ])->assertCreated();

        $id = $respuesta->json('modulo.id');

        $this->actingAs($admin)->putJson("/api/panel/catalogo/modulos/{$id}", [
            'codigo' => 'MOD-99',
            'nombre' => 'Lógica de programación I',
            'estado_comercial' => 'abierto',
        ])->assertOk()->assertJsonPath('modulo.nombre', 'Lógica de programación I');
    }
}
