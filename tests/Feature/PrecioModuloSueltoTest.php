<?php

namespace Tests\Feature;

use App\Enums\EstadoMatricula;
use App\Enums\EstadoPlanificacion;
use App\Enums\Rol;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\MetodoPago;
use App\Models\Modulo;
use App\Models\User;
use App\Services\MatriculaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrecioModuloSueltoTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(Rol $rol): User
    {
        return User::factory()->create(['rol' => $rol, 'estado' => 'activo']);
    }

    private function matriculaSuelta(User $estudiante, ?float $precio): Matricula
    {
        $modulo = Modulo::create([
            'codigo' => 'MOD-'.fake()->unique()->numerify('###'),
            'nombre' => 'Módulo suelto',
            'precio' => $precio,
        ]);
        $grupo = Grupo::create([
            'modulo_id' => $modulo->id,
            'docente_id' => $this->usuario(Rol::Docente)->id,
            'nombre' => 'Grupo suelto',
            'modalidad' => 'virtual',
            'estado' => EstadoPlanificacion::Programado,
        ]);

        return app(MatriculaService::class)->inscribirEnGrupoSuelto($estudiante, $grupo);
    }

    public function test_el_administrador_fija_el_precio_de_un_modulo(): void
    {
        $admin = $this->usuario(Rol::Administrador);

        $this->actingAs($admin)->postJson('/api/panel/catalogo/modulos', [
            'codigo' => 'MOD-900',
            'nombre' => 'Excel básico',
            'estado_comercial' => 'abierto',
            'precio' => 350.5,
        ])->assertCreated()->assertJsonPath('modulo.precio', 350.5);

        $this->actingAs($admin)->postJson('/api/panel/catalogo/modulos', [
            'codigo' => 'MOD-901',
            'nombre' => 'Excel avanzado',
            'estado_comercial' => 'abierto',
            'precio' => -5,
        ])->assertUnprocessable()->assertJsonValidationErrors('precio');
    }

    public function test_la_matricula_de_modulo_suelto_muestra_el_precio_del_modulo(): void
    {
        $estudiante = $this->usuario(Rol::Estudiante);
        $this->matriculaSuelta($estudiante, 300);

        $this->actingAs($estudiante)->getJson('/api/panel/mis-matriculas')
            ->assertOk()
            ->assertJsonPath('data.0.matricula.importe', 300);
    }

    public function test_el_estudiante_paga_un_modulo_suelto_por_el_precio_del_modulo(): void
    {
        $estudiante = $this->usuario(Rol::Estudiante);
        $matricula = $this->matriculaSuelta($estudiante, 300);
        MetodoPago::create(['user_id' => $estudiante->id, 'tipo' => 'qr', 'titular' => 'DEMO', 'predeterminado' => true]);

        $this->actingAs($estudiante)->postJson("/api/panel/matriculas/{$matricula->id}/pagar", [])
            ->assertCreated()
            ->assertJsonPath('transaccion.monto', 300);

        $this->assertSame(EstadoMatricula::Activo, $matricula->fresh()->estado);
    }

    public function test_un_modulo_sin_precio_no_se_puede_pagar(): void
    {
        $estudiante = $this->usuario(Rol::Estudiante);
        $matricula = $this->matriculaSuelta($estudiante, null);
        MetodoPago::create(['user_id' => $estudiante->id, 'tipo' => 'qr', 'titular' => 'DEMO', 'predeterminado' => true]);

        $this->actingAs($estudiante)->postJson("/api/panel/matriculas/{$matricula->id}/pagar", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('monto');
    }
}
