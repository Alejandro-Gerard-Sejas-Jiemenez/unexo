<?php

namespace Tests\Feature;

use App\Enums\EstadoMatricula;
use App\Enums\Rol;
use App\Models\Grupo;
use App\Models\InscripcionGrupo;
use App\Models\Matricula;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaGruposConHorarios;
use Tests\TestCase;

class GrupoDetalleTest extends TestCase
{
    use CreaGruposConHorarios;
    use RefreshDatabase;

    private const ORDEN_HORARIOS = ['Lunes', '09:00–11:00', 'Martes', '19:00–21:00', 'Sábado', '08:00–10:00'];

    private function grupoDesordenado(?User $docente = null): Grupo
    {
        $grupo = $this->crearGrupoConHorarios([
            [6, '08:00', '10:00'],
            [1, '09:00', '11:00'],
            [2, '19:00', '21:00'],
        ], null, ['enlace_videoconferencia' => 'https://meet.example.test/detalle']);

        if ($docente) {
            $grupo->update(['docente_id' => $docente->id]);
        }

        return $grupo;
    }

    private function inscribir(User $estudiante, Grupo $grupo, EstadoMatricula $estado): void
    {
        $matricula = Matricula::create([
            'estudiante_id' => $estudiante->id,
            'estado' => $estado,
            'fecha_matricula' => today(),
        ]);
        InscripcionGrupo::create(['matricula_id' => $matricula->id, 'grupo_id' => $grupo->id]);
    }

    private function usuario(Rol $rol): User
    {
        return User::factory()->create(['rol' => $rol]);
    }

    // CA-30
    public function test_el_administrador_ve_los_horarios_de_lunes_a_domingo(): void
    {
        $grupo = $this->grupoDesordenado();

        $this->actingAs($this->usuario(Rol::Administrador))
            ->get(route('grupos.show', $grupo))
            ->assertOk()
            ->assertSeeInOrder(self::ORDEN_HORARIOS);
    }

    // CA-30
    public function test_el_docente_asignado_ve_los_horarios_y_el_periodo(): void
    {
        $docente = $this->usuario(Rol::Docente);
        $grupo = $this->grupoDesordenado($docente);

        $this->actingAs($docente)
            ->get(route('mis-grupos.show', $grupo))
            ->assertOk()
            ->assertSee($grupo->periodo_texto)
            ->assertSeeInOrder(self::ORDEN_HORARIOS);
    }

    // CA-41
    public function test_un_docente_no_asignado_recibe_acceso_denegado(): void
    {
        $grupo = $this->grupoDesordenado();

        $this->actingAs($this->usuario(Rol::Docente))
            ->get(route('mis-grupos.show', $grupo))
            ->assertForbidden();
    }

    /** @dataProvider estadosVigentes */
    // CA-30
    public function test_el_estudiante_con_matricula_vigente_ve_los_horarios(EstadoMatricula $estado): void
    {
        $estudiante = $this->usuario(Rol::Estudiante);
        $grupo = $this->grupoDesordenado();
        $this->inscribir($estudiante, $grupo, $estado);

        $this->actingAs($estudiante)
            ->get(route('mis-cursos.show', $grupo))
            ->assertOk()
            ->assertSee($grupo->periodo_texto)
            ->assertSee($grupo->nombre)
            ->assertSeeInOrder(self::ORDEN_HORARIOS);
    }

    public static function estadosVigentes(): array
    {
        return [
            'pendiente de pago' => [EstadoMatricula::Pendiente],
            'activa' => [EstadoMatricula::Activo],
        ];
    }

    // CA-41
    public function test_el_estudiante_retirado_recibe_acceso_denegado(): void
    {
        $estudiante = $this->usuario(Rol::Estudiante);
        $grupo = $this->grupoDesordenado();
        $this->inscribir($estudiante, $grupo, EstadoMatricula::Retirado);

        $this->actingAs($estudiante)
            ->get(route('mis-cursos.show', $grupo))
            ->assertForbidden()
            ->assertSee('No tienes acceso a este grupo.', false);
    }

    // CA-41
    public function test_el_estudiante_sin_matricula_recibe_acceso_denegado(): void
    {
        $grupo = $this->grupoDesordenado();

        $this->actingAs($this->usuario(Rol::Estudiante))
            ->get(route('mis-cursos.show', $grupo))
            ->assertForbidden()
            ->assertSee('No tienes acceso a este grupo.', false);
    }

    // CA-41
    public function test_el_detalle_del_estudiante_no_muestra_el_enlace_de_videoconferencia(): void
    {
        $estudiante = $this->usuario(Rol::Estudiante);
        $grupo = $this->grupoDesordenado();
        $this->inscribir($estudiante, $grupo, EstadoMatricula::Activo);

        $this->actingAs($estudiante)
            ->get(route('mis-cursos.show', $grupo))
            ->assertOk()
            ->assertDontSee('meet.example.test');
    }

    // CA-41
    public function test_mis_cursos_enlaza_al_detalle_solo_si_la_matricula_esta_vigente(): void
    {
        $estudiante = $this->usuario(Rol::Estudiante);
        $vigente = $this->grupoDesordenado();
        $retirado = $this->grupoDesordenado();
        $this->inscribir($estudiante, $vigente, EstadoMatricula::Activo);
        $this->inscribir($estudiante, $retirado, EstadoMatricula::Retirado);

        $this->actingAs($estudiante)
            ->get(route('mis-cursos'))
            ->assertOk()
            ->assertSee(route('mis-cursos.show', $vigente), false)
            ->assertDontSee(route('mis-cursos.show', $retirado), false);
    }
}
