<?php

namespace Tests\Feature;

use App\Enums\EstadoComercial;
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

class GestionAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['rol' => Rol::Administrador]);
    }

    private function programa(): Programa
    {
        return Programa::create(['codigo' => 'P-1', 'nombre' => 'Diplomado', 'version' => '1.0', 'estado_comercial' => EstadoComercial::Abierto]);
    }

    public function test_el_admin_crea_una_cohorte(): void
    {
        $programa = $this->programa();

        $this->actingAs($this->admin())->post(route('cohortes.store'), [
            'programa_id' => $programa->id,
            'nombre' => 'Cohorte 2026-2',
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => '2026-12-01',
            'estado' => EstadoPlanificacion::Programado->value,
        ])->assertRedirect();

        $this->assertDatabaseHas('cohortes', ['nombre' => 'Cohorte 2026-2', 'programa_id' => $programa->id]);
    }

    public function test_la_cohorte_rechaza_fecha_fin_anterior_al_inicio(): void
    {
        $programa = $this->programa();

        $this->actingAs($this->admin())->post(route('cohortes.store'), [
            'programa_id' => $programa->id,
            'nombre' => 'Mala',
            'fecha_inicio' => '2026-12-01',
            'fecha_fin' => '2026-09-01',
            'estado' => EstadoPlanificacion::Programado->value,
        ])->assertSessionHasErrors('fecha_fin');
    }

    public function test_un_grupo_solo_acepta_docentes_como_docente(): void
    {
        $modulo = Modulo::create(['codigo' => 'M-1', 'nombre' => 'Excel', 'estado_comercial' => EstadoComercial::Abierto]);
        $estudiante = User::factory()->create(['rol' => Rol::Estudiante]);

        $this->actingAs($this->admin())->post(route('grupos.store'), [
            'modulo_id' => $modulo->id,
            'docente_id' => $estudiante->id,
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => '2026-10-01',
            'modalidad' => 'virtual',
            'estado' => EstadoPlanificacion::Programado->value,
            'horarios' => [['dia_semana' => 1, 'hora_inicio' => '19:00', 'hora_fin' => '21:00']],
        ])->assertSessionHasErrors('docente_id');
    }

    public function test_matricular_en_cohorte_crea_inscripciones_por_grupo(): void
    {
        $programa = $this->programa();
        $docente = User::factory()->create(['rol' => Rol::Docente]);
        $estudiante = User::factory()->create(['rol' => Rol::Estudiante]);
        $cohorte = Cohorte::create(['programa_id' => $programa->id, 'nombre' => 'C1', 'fecha_inicio' => '2026-09-01', 'fecha_fin' => '2026-12-01', 'estado' => EstadoPlanificacion::Programado]);
        foreach (['Excel', 'Power BI'] as $n) {
            $m = Modulo::create(['codigo' => 'M-'.$n, 'nombre' => $n, 'estado_comercial' => EstadoComercial::Abierto]);
            Grupo::create(['modulo_id' => $m->id, 'cohorte_id' => $cohorte->id, 'docente_id' => $docente->id, 'nombre' => 'Grupo 1', 'modalidad' => 'virtual', 'estado' => EstadoPlanificacion::Programado]);
        }

        $this->actingAs($this->admin())->post(route('matriculas.store'), [
            'estudiante_id' => $estudiante->id,
            'tipo' => 'cohorte',
            'cohorte_id' => $cohorte->id,
        ])->assertRedirect();

        $matricula = Matricula::where('estudiante_id', $estudiante->id)->firstOrFail();
        $this->assertSame(EstadoMatricula::Pendiente, $matricula->estado);
        $this->assertSame(2, $matricula->inscripciones()->count());
    }

    public function test_no_se_puede_matricular_dos_veces_en_la_misma_cohorte(): void
    {
        $programa = $this->programa();
        $estudiante = User::factory()->create(['rol' => Rol::Estudiante]);
        $cohorte = Cohorte::create(['programa_id' => $programa->id, 'nombre' => 'C1', 'fecha_inicio' => '2026-09-01', 'fecha_fin' => '2026-12-01', 'estado' => EstadoPlanificacion::Programado]);

        $datos = ['estudiante_id' => $estudiante->id, 'tipo' => 'cohorte', 'cohorte_id' => $cohorte->id];
        $this->actingAs($this->admin())->post(route('matriculas.store'), $datos);
        $this->actingAs($this->admin())->post(route('matriculas.store'), $datos)->assertSessionHasErrors('cohorte_id');

        $this->assertSame(1, Matricula::where('estudiante_id', $estudiante->id)->count());
    }

    public function test_aprobar_pago_cambia_el_estado_a_activo(): void
    {
        $estudiante = User::factory()->create(['rol' => Rol::Estudiante]);
        $matricula = Matricula::create(['estudiante_id' => $estudiante->id, 'estado' => EstadoMatricula::Pendiente, 'fecha_matricula' => today()]);

        $this->actingAs($this->admin())->patch(route('matriculas.estado', $matricula), ['estado' => EstadoMatricula::Activo->value])
            ->assertRedirect();

        $this->assertSame(EstadoMatricula::Activo, $matricula->fresh()->estado);
    }

    public function test_un_docente_no_entra_a_la_gestion(): void
    {
        $docente = User::factory()->create(['rol' => Rol::Docente]);
        $this->actingAs($docente)->get(route('cohortes.index'))->assertForbidden();
        $this->actingAs($docente)->get(route('grupos.index'))->assertForbidden();
        $this->actingAs($docente)->get(route('matriculas.index'))->assertForbidden();
    }
}
