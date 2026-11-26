<?php

namespace Tests\Feature;

use App\Enums\EstadoMatricula;
use App\Enums\EstadoPlanificacion;
use App\Enums\Rol;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** CU6 (matriculación flexible) y CU7 (estado de la matrícula) por la API. */
class MatriculaPanelApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['rol' => Rol::Administrador, 'estado' => 'activo']);
    }

    private function estudiante(): User
    {
        return User::factory()->create(['rol' => Rol::Estudiante, 'estado' => 'activo']);
    }

    private function docente(): User
    {
        return User::factory()->create(['rol' => Rol::Docente, 'estado' => 'activo']);
    }

    /** Edición con dos grupos aperturados, lista para matricular. */
    private function edicionConGrupos(int $modulos = 2): Cohorte
    {
        $docente = $this->docente();

        $programa = Programa::create([
            'codigo' => 'PRG-'.fake()->unique()->numerify('###'),
            'nombre' => 'Programa '.fake()->unique()->word(),
            'version' => '1.0',
            'estado_comercial' => 'abierto',
        ]);

        $cohorte = Cohorte::create([
            'programa_id' => $programa->id,
            'nombre' => 'Edición '.fake()->unique()->numerify('##'),
            'fecha_inicio' => today(),
            'fecha_fin' => today()->addMonths(3),
            'estado' => EstadoPlanificacion::Programado,
        ]);

        foreach (range(1, $modulos) as $i) {
            $modulo = Modulo::create([
                'codigo' => 'MOD-'.fake()->unique()->numerify('###'),
                'nombre' => 'Módulo '.fake()->unique()->word(),
            ]);
            $programa->modulos()->attach($modulo->id, ['orden' => $i]);

            Grupo::create([
                'modulo_id' => $modulo->id,
                'cohorte_id' => $cohorte->id,
                'docente_id' => $docente->id,
                'nombre' => "Grupo {$i}",
                'modalidad' => 'virtual',
                'estado' => EstadoPlanificacion::Programado,
            ]);
        }

        return $cohorte;
    }

    private function grupoSuelto(): Grupo
    {
        return Grupo::create([
            'modulo_id' => Modulo::create([
                'codigo' => 'MOD-'.fake()->unique()->numerify('###'),
                'nombre' => 'Suelto '.fake()->unique()->word(),
            ])->id,
            'docente_id' => $this->docente()->id,
            'nombre' => 'Grupo suelto '.fake()->unique()->numerify('##'),
            'modalidad' => 'virtual',
            'estado' => EstadoPlanificacion::Programado,
        ]);
    }

    public function test_matricular_a_una_edicion_inscribe_en_todos_sus_grupos(): void
    {
        $admin = $this->admin();
        $estudiante = $this->estudiante();
        $cohorte = $this->edicionConGrupos(3);

        $respuesta = $this->actingAs($admin)->postJson('/api/panel/matriculas', [
            'estudiante_id' => $estudiante->id,
            'tipo' => 'cohorte',
            'cohorte_id' => $cohorte->id,
        ])->assertCreated();

        $matricula = Matricula::findOrFail($respuesta->json('matricula.id'));

        $this->assertSame(3, $matricula->inscripciones()->count());
        $this->assertSame(EstadoMatricula::Pendiente, $matricula->estado);
    }

    public function test_matricular_a_un_modulo_suelto_genera_una_sola_inscripcion(): void
    {
        $admin = $this->admin();
        $estudiante = $this->estudiante();
        $grupo = $this->grupoSuelto();

        $respuesta = $this->actingAs($admin)->postJson('/api/panel/matriculas', [
            'estudiante_id' => $estudiante->id,
            'tipo' => 'suelto',
            'grupo_id' => $grupo->id,
        ])->assertCreated();

        $matricula = Matricula::findOrFail($respuesta->json('matricula.id'));

        $this->assertSame(1, $matricula->inscripciones()->count());
        $this->assertNull($matricula->cohorte_id);
    }

    public function test_no_se_matricula_dos_veces_en_la_misma_edicion(): void
    {
        $admin = $this->admin();
        $estudiante = $this->estudiante();
        $cohorte = $this->edicionConGrupos();

        $cuerpo = ['estudiante_id' => $estudiante->id, 'tipo' => 'cohorte', 'cohorte_id' => $cohorte->id];

        $this->actingAs($admin)->postJson('/api/panel/matriculas', $cuerpo)->assertCreated();

        $this->actingAs($admin)->postJson('/api/panel/matriculas', $cuerpo)
            ->assertStatus(422)
            ->assertJsonPath('errors.cohorte_id.0', 'El estudiante ya está matriculado en esta edición.');
    }

    public function test_el_tipo_decide_que_destino_es_obligatorio(): void
    {
        $admin = $this->admin();
        $estudiante = $this->estudiante();

        $this->actingAs($admin)->postJson('/api/panel/matriculas', [
            'estudiante_id' => $estudiante->id,
            'tipo' => 'cohorte',
        ])->assertStatus(422)->assertJsonValidationErrors(['cohorte_id']);

        $this->actingAs($admin)->postJson('/api/panel/matriculas', [
            'estudiante_id' => $estudiante->id,
            'tipo' => 'suelto',
        ])->assertStatus(422)->assertJsonValidationErrors(['grupo_id']);
    }

    public function test_solo_se_matricula_a_quien_tiene_rol_estudiante(): void
    {
        $admin = $this->admin();
        $cohorte = $this->edicionConGrupos();

        $this->actingAs($admin)->postJson('/api/panel/matriculas', [
            'estudiante_id' => $this->docente()->id,
            'tipo' => 'cohorte',
            'cohorte_id' => $cohorte->id,
        ])->assertStatus(422)->assertJsonValidationErrors(['estudiante_id']);
    }

    public function test_el_detalle_trae_inscripciones_y_comprobantes(): void
    {
        $admin = $this->admin();
        $estudiante = $this->estudiante();
        $cohorte = $this->edicionConGrupos(2);

        $creada = $this->actingAs($admin)->postJson('/api/panel/matriculas', [
            'estudiante_id' => $estudiante->id,
            'tipo' => 'cohorte',
            'cohorte_id' => $cohorte->id,
        ])->assertCreated();

        $this->actingAs($admin)
            ->getJson("/api/panel/matriculas/{$creada->json('matricula.id')}")
            ->assertOk()
            ->assertJsonCount(2, 'data.inscripciones')
            ->assertJsonPath('data.estudiante.nombre', $estudiante->nombre_completo);
    }

    public function test_el_listado_filtra_por_estado_y_por_origen(): void
    {
        $admin = $this->admin();
        $cohorte = $this->edicionConGrupos();

        $this->actingAs($admin)->postJson('/api/panel/matriculas', [
            'estudiante_id' => $this->estudiante()->id,
            'tipo' => 'cohorte',
            'cohorte_id' => $cohorte->id,
        ])->assertCreated();

        $this->actingAs($admin)->getJson('/api/panel/matriculas?estado=pendiente')
            ->assertOk()->assertJsonCount(1, 'data');

        $this->actingAs($admin)->getJson('/api/panel/matriculas?estado=activo')
            ->assertOk()->assertJsonCount(0, 'data');

        $this->actingAs($admin)->getJson('/api/panel/matriculas?origen=manual')
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_retirar_conserva_el_historial(): void
    {
        $admin = $this->admin();
        $cohorte = $this->edicionConGrupos(2);

        $creada = $this->actingAs($admin)->postJson('/api/panel/matriculas', [
            'estudiante_id' => $this->estudiante()->id,
            'tipo' => 'cohorte',
            'cohorte_id' => $cohorte->id,
        ])->assertCreated();

        $id = $creada->json('matricula.id');

        $this->actingAs($admin)->patchJson("/api/panel/matriculas/{$id}/estado", ['estado' => 'retirado'])
            ->assertOk()
            ->assertJsonPath('matricula.estado', 'retirado');

        $this->assertSame(2, Matricula::findOrFail($id)->inscripciones()->count());
    }

    public function test_un_estudiante_no_entra_al_modulo_de_matriculas(): void
    {
        $this->actingAs($this->estudiante())->getJson('/api/panel/matriculas')->assertForbidden();
    }

    public function test_las_opciones_traen_estudiantes_ediciones_y_grupos_sueltos(): void
    {
        $this->estudiante();
        $this->edicionConGrupos();
        $this->grupoSuelto();

        $this->actingAs($this->admin())->getJson('/api/panel/matriculas/opciones')
            ->assertOk()
            ->assertJsonStructure(['estudiantes', 'cohortes', 'grupos_sueltos', 'estados'])
            ->assertJsonCount(1, 'grupos_sueltos');
    }
}
