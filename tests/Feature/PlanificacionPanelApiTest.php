<?php

namespace Tests\Feature;

use App\Enums\EstadoPlanificacion;
use App\Enums\Rol;
use App\Models\Aula;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** CU4 (ediciones y apertura de grupos) y CU5 (logística) por la API del panel. */
class PlanificacionPanelApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['rol' => Rol::Administrador, 'estado' => 'activo']);
    }

    private function docente(): User
    {
        return User::factory()->create(['rol' => Rol::Docente, 'estado' => 'activo']);
    }

    /** Un programa con dos módulos en su plan de estudio. */
    private function programaConPlan(int $modulos = 2): Programa
    {
        $programa = Programa::create([
            'codigo' => 'PRG-'.fake()->unique()->numerify('###'),
            'nombre' => 'Programa '.fake()->unique()->word(),
            'version' => '1.0',
            'estado_comercial' => 'abierto',
        ]);

        foreach (range(1, $modulos) as $i) {
            $modulo = Modulo::create([
                'codigo' => 'MOD-'.fake()->unique()->numerify('###'),
                'nombre' => 'Módulo '.fake()->unique()->word(),
                'estado_comercial' => 'abierto',
            ]);
            $programa->modulos()->attach($modulo->id, ['orden' => $i]);
        }

        return $programa;
    }

    /**
     * Edición lista para aperturar: el servicio exige que exista al menos un
     * docente activo, porque sin docente no se puede abrir un grupo.
     */
    private function edicion(Programa $programa): Cohorte
    {
        $this->docente();

        return Cohorte::create([
            'programa_id' => $programa->id,
            'nombre' => 'Edición '.fake()->unique()->numerify('##'),
            'fecha_inicio' => today(),
            'fecha_fin' => today()->addMonths(3),
            'estado' => EstadoPlanificacion::Programado,
        ]);
    }

    // ------------------------------------------------------------- CU4

    public function test_crear_una_edicion_sin_nombre_la_nombra_sola(): void
    {
        $programa = $this->programaConPlan();

        $respuesta = $this->actingAs($this->admin())->postJson('/api/panel/cohortes', [
            'programa_id' => $programa->id,
            'fecha_inicio' => today()->toDateString(),
            'fecha_fin' => today()->addMonths(2)->toDateString(),
            'estado' => 'programado',
        ])->assertCreated();

        $this->assertNotEmpty($respuesta->json('cohorte.nombre'));
    }

    public function test_la_fecha_de_fin_no_puede_ser_anterior_a_la_de_inicio(): void
    {
        $programa = $this->programaConPlan();

        $this->actingAs($this->admin())->postJson('/api/panel/cohortes', [
            'programa_id' => $programa->id,
            'fecha_inicio' => today()->toDateString(),
            'fecha_fin' => today()->subDay()->toDateString(),
            'estado' => 'programado',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.fecha_fin.0', 'La fecha de fin no puede ser anterior a la de inicio.');
    }

    public function test_editar_sin_nombre_conserva_el_actual(): void
    {
        $programa = $this->programaConPlan();
        $edicion = $this->edicion($programa);
        $nombre = $edicion->nombre;

        $this->actingAs($this->admin())->putJson("/api/panel/cohortes/{$edicion->id}", [
            'programa_id' => $programa->id,
            'nombre' => '',
            'fecha_inicio' => $edicion->fecha_inicio->toDateString(),
            'fecha_fin' => $edicion->fecha_fin->toDateString(),
            'estado' => 'programado',
        ])->assertStatus(422); // el nombre es obligatorio al editar

        $this->assertSame($nombre, $edicion->fresh()->nombre);
    }

    public function test_aperturar_genera_un_grupo_por_modulo_del_plan(): void
    {
        $admin = $this->admin();
        $programa = $this->programaConPlan(3);
        $edicion = $this->edicion($programa);

        $this->actingAs($admin)
            ->postJson("/api/panel/cohortes/{$edicion->id}/aperturar-grupos", ['modalidad' => 'virtual'])
            ->assertOk()
            ->assertJsonPath('creados', 3);

        $this->assertSame(3, $edicion->grupos()->count());

        // Una segunda apertura no duplica: ya no quedan módulos sin grupo.
        $this->actingAs($admin)
            ->postJson("/api/panel/cohortes/{$edicion->id}/aperturar-grupos", [])
            ->assertOk()
            ->assertJsonPath('creados', 0);

        $this->assertSame(3, $edicion->grupos()->count());
    }

    public function test_el_detalle_lista_los_modulos_que_todavia_no_tienen_grupo(): void
    {
        $admin = $this->admin();
        $programa = $this->programaConPlan(2);
        $edicion = $this->edicion($programa);

        $this->actingAs($admin)
            ->getJson("/api/panel/cohortes/{$edicion->id}")
            ->assertOk()
            ->assertJsonCount(2, 'modulos_pendientes');

        $this->actingAs($admin)
            ->postJson("/api/panel/cohortes/{$edicion->id}/aperturar-grupos", [])
            ->assertOk();

        $this->actingAs($admin)
            ->getJson("/api/panel/cohortes/{$edicion->id}")
            ->assertOk()
            ->assertJsonCount(0, 'modulos_pendientes');
    }

    public function test_cambiar_el_estado_puede_propagarse_a_los_grupos(): void
    {
        $admin = $this->admin();
        $edicion = $this->edicion($this->programaConPlan(2));

        $this->actingAs($admin)
            ->postJson("/api/panel/cohortes/{$edicion->id}/aperturar-grupos", [])
            ->assertOk();

        $this->actingAs($admin)
            ->patchJson("/api/panel/cohortes/{$edicion->id}/estado", [
                'estado' => 'cancelado',
                'propagar_a_grupos' => true,
            ])
            ->assertOk()
            ->assertJsonPath('cohorte.estado', 'cancelado');

        $this->assertSame(
            0,
            $edicion->grupos()->where('estado', '!=', EstadoPlanificacion::Cancelado->value)->count(),
        );
    }

    // ------------------------------------------------------------- CU5

    public function test_crear_un_grupo_con_sus_horarios(): void
    {
        $admin = $this->admin();
        $docente = $this->docente();
        $modulo = Modulo::create(['codigo' => 'MOD-SOLO', 'nombre' => 'Módulo suelto']);

        $respuesta = $this->actingAs($admin)->postJson('/api/panel/grupos', [
            'modulo_id' => $modulo->id,
            'docente_id' => $docente->id,
            'modalidad' => 'virtual',
            'enlace_videoconferencia' => 'https://meet.example.com/abc',
            'estado' => 'programado',
            'fecha_inicio' => today()->toDateString(),
            'fecha_fin' => today()->addMonth()->toDateString(),
            'horarios' => [
                ['dia_semana' => 1, 'hora_inicio' => '19:00', 'hora_fin' => '21:00'],
                ['dia_semana' => 3, 'hora_inicio' => '19:00', 'hora_fin' => '21:00'],
            ],
        ])->assertCreated();

        $this->assertCount(2, $respuesta->json('grupo.horarios'));
        $this->assertSame('Lunes', $respuesta->json('grupo.horarios.0.dia_label'));
    }

    public function test_un_grupo_presencial_exige_aula(): void
    {
        $admin = $this->admin();
        $docente = $this->docente();
        $modulo = Modulo::create(['codigo' => 'MOD-PRES', 'nombre' => 'Presencial']);

        $this->actingAs($admin)->postJson('/api/panel/grupos', [
            'modulo_id' => $modulo->id,
            'docente_id' => $docente->id,
            'modalidad' => 'presencial',
            'estado' => 'programado',
            'fecha_inicio' => today()->toDateString(),
            'fecha_fin' => today()->addMonth()->toDateString(),
            'horarios' => [['dia_semana' => 2, 'hora_inicio' => '08:00', 'hora_fin' => '10:00']],
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.aula_id.0', 'Debe asignar un aula física para grupos en modalidad presencial.');
    }

    public function test_el_horario_no_puede_terminar_antes_de_empezar(): void
    {
        $admin = $this->admin();
        $docente = $this->docente();
        $modulo = Modulo::create(['codigo' => 'MOD-HOR', 'nombre' => 'Horario raro']);

        $this->actingAs($admin)->postJson('/api/panel/grupos', [
            'modulo_id' => $modulo->id,
            'docente_id' => $docente->id,
            'modalidad' => 'virtual',
            'estado' => 'programado',
            'fecha_inicio' => today()->toDateString(),
            'fecha_fin' => today()->addMonth()->toDateString(),
            'horarios' => [['dia_semana' => 2, 'hora_inicio' => '21:00', 'hora_fin' => '19:00']],
        ])->assertStatus(422);
    }

    public function test_el_listado_de_grupos_separa_sueltos_de_los_de_edicion(): void
    {
        $admin = $this->admin();
        $edicion = $this->edicion($this->programaConPlan(1));

        $this->actingAs($admin)
            ->postJson("/api/panel/cohortes/{$edicion->id}/aperturar-grupos", [])
            ->assertOk();

        Grupo::create([
            'modulo_id' => Modulo::create(['codigo' => 'MOD-LIBRE', 'nombre' => 'Libre'])->id,
            'docente_id' => $this->docente()->id,
            'nombre' => 'Grupo suelto',
            'modalidad' => 'virtual',
            'estado' => EstadoPlanificacion::Programado,
        ]);

        $this->actingAs($admin)->getJson('/api/panel/grupos?tipo=suelto')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'Grupo suelto');

        $this->actingAs($admin)->getJson('/api/panel/grupos?tipo=cohorte')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_las_opciones_traen_docentes_aulas_y_catalogos(): void
    {
        $this->docente();
        Aula::create(['nombre' => 'Aula 1', 'capacidad' => 30]);

        $this->actingAs($this->admin())->getJson('/api/panel/planificacion/opciones')
            ->assertOk()
            ->assertJsonStructure(['programas', 'modulos', 'docentes', 'aulas', 'estados', 'modalidades', 'dias'])
            ->assertJsonCount(7, 'dias');
    }

    public function test_un_docente_no_planifica(): void
    {
        $docente = $this->docente();

        $this->actingAs($docente)->getJson('/api/panel/cohortes')->assertForbidden();
        $this->actingAs($docente)->getJson('/api/panel/grupos')->assertForbidden();
    }

    public function test_el_detalle_del_grupo_incluye_periodo_y_lista_de_inscritos(): void
    {
        $admin = $this->admin();
        $estudiante = User::factory()->create(['rol' => Rol::Estudiante, 'estado' => 'activo']);
        $modulo = Modulo::create(['codigo' => 'MOD-DET', 'nombre' => 'Módulo Detalle']);
        $grupo = Grupo::create([
            'modulo_id' => $modulo->id,
            'docente_id' => $this->docente()->id,
            'nombre' => 'Grupo Detalle',
            'modalidad' => 'virtual',
            'estado' => EstadoPlanificacion::EnCurso,
            'cupo_minimo' => 5,
            'fecha_inicio' => today(),
            'fecha_fin' => today()->addMonth(),
        ]);

        $matricula = \App\Models\Matricula::create([
            'estudiante_id' => $estudiante->id,
            'estado' => \App\Enums\EstadoMatricula::Activo,
            'fecha_matricula' => today(),
        ]);
        \App\Models\InscripcionGrupo::create(['matricula_id' => $matricula->id, 'grupo_id' => $grupo->id]);

        $this->actingAs($admin)->getJson("/api/panel/grupos/{$grupo->id}")
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Grupo Detalle')
            ->assertJsonPath('data.inscripciones_count', 1)
            ->assertJsonCount(1, 'data.inscritos');
    }
}
