<?php

namespace Tests\Feature;

use App\Enums\DiaSemana;
use App\Enums\EstadoComercial;
use App\Enums\EstadoPlanificacion;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Modulo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaGruposConHorarios;
use Tests\Concerns\EnviaFormularioDeGrupo;
use Tests\TestCase;

class GrupoPeriodoTest extends TestCase
{
    use CreaGruposConHorarios;
    use EnviaFormularioDeGrupo;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    // RF-15: el período del grupo de edición es el de la edición.
    public function test_el_grupo_de_edicion_toma_el_periodo_de_su_edicion(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $grupo = $this->crearGrupoConHorarios([], $edicion)->fresh();

        $this->assertNull($grupo->getRawOriginal('fecha_inicio'));
        $this->assertSame('2026-05-04', $grupo->periodo_inicio->toDateString());
        $this->assertSame('2026-06-20', $grupo->periodo_fin->toDateString());
        $this->assertSame('04/05/2026 – 20/06/2026', $grupo->periodo_texto);
    }

    public function test_el_grupo_suelto_usa_sus_propias_fechas(): void
    {
        $grupo = $this->crearGrupoConHorarios()->fresh();

        $this->assertSame('2026-03-01', $grupo->periodo_inicio->toDateString());
        $this->assertSame('2026-03-30', $grupo->periodo_fin->toDateString());
        $this->assertSame('01/03/2026 – 30/03/2026', $grupo->periodo_texto);
    }

    public function test_el_periodo_funciona_con_la_relacion_cohorte_cargada_o_sin_cargar(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $id = $this->crearGrupoConHorarios([], $edicion)->id;

        $sinCargar = Grupo::find($id);
        $cargado = Grupo::with('cohorte')->find($id);

        $this->assertFalse($sinCargar->relationLoaded('cohorte'));
        $this->assertSame('04/05/2026 – 20/06/2026', $sinCargar->periodo_texto);
        $this->assertSame('04/05/2026 – 20/06/2026', $cargado->periodo_texto);
    }

    // CA-19: cambiar las fechas de la edición se refleja en sus grupos.
    public function test_el_cambio_de_fechas_de_la_edicion_se_refleja_en_el_grupo(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $id = $this->crearGrupoConHorarios([], $edicion)->id;

        $edicion->update(['fecha_inicio' => '2026-07-01', 'fecha_fin' => '2026-08-15']);

        $this->assertSame('01/07/2026 – 15/08/2026', Grupo::find($id)->periodo_texto);
    }

    public function test_el_texto_del_periodo_es_neutro_si_faltan_datos(): void
    {
        $grupo = new Grupo;

        $this->assertNull($grupo->periodo_inicio);
        $this->assertNull($grupo->periodo_fin);
        $this->assertSame('Sin período definido', $grupo->periodo_texto);
    }

    public function test_la_fase_se_calcula_sobre_el_periodo_efectivo(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $id = $this->crearGrupoConHorarios([], $edicion)->id;

        $this->travelTo(now()->setDate(2026, 5, 3));
        $this->assertSame('por-iniciar', Grupo::find($id)->fase);

        $this->travelTo(now()->setDate(2026, 6, 20));
        $this->assertSame('en-curso', Grupo::find($id)->fase);
        $this->assertSame('En curso', Grupo::find($id)->faseEtiqueta);

        $this->travelTo(now()->setDate(2026, 6, 21));
        $this->assertSame('finalizado', Grupo::find($id)->fase);
    }

    public function test_el_grupo_cancelado_sigue_con_fase_cancelado(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $grupo = $this->crearGrupoConHorarios([], $edicion, ['estado' => EstadoPlanificacion::Cancelado]);

        $this->assertSame('cancelado', $grupo->fresh()->fase);
        $this->assertSame('Cancelado', $grupo->fresh()->faseEtiqueta);
    }

    public function test_los_horarios_salen_ordenados_por_dia_y_hora(): void
    {
        $grupo = $this->crearGrupoConHorarios([
            [5, '19:00', '21:00'],
            [1, '20:00', '22:00'],
            [1, '08:00', '10:00'],
        ]);

        $rangos = $grupo->horarios->map(fn ($h) => $h->dia_semana->value.' '.$h->rango)->all();

        $this->assertSame(['1 08:00–10:00', '1 20:00–22:00', '5 19:00–21:00'], $rangos);
        $this->assertSame(DiaSemana::from(1), $grupo->horarios->first()->dia_semana);
    }

    // RF-26 / D4: solape sobre el período efectivo, extremos incluidos.
    public function test_solape_con_extremos_incluidos_en_ambos_tipos_de_grupo(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $deEdicion = $this->crearGrupoConHorarios([], $edicion);
        $suelto = $this->crearGrupoConHorarios();

        $ids = fn (string $desde, string $hasta) => Grupo::queSolapaCon($desde, $hasta)->pluck('id')->all();

        $this->assertSame([$deEdicion->id], $ids('2026-06-20', '2026-07-01'));
        $this->assertSame([$deEdicion->id], $ids('2026-04-01', '2026-05-04'));
        $this->assertSame([], $ids('2026-06-21', '2026-07-01'));
        $this->assertSame([], $ids('2026-04-01', '2026-05-03'));
        $this->assertSame([$suelto->id], $ids('2026-03-30', '2026-04-10'));
        $this->assertSame([$suelto->id], $ids('2026-02-01', '2026-03-01'));
        $this->assertSame([], $ids('2026-03-31', '2026-04-10'));
        $this->assertEqualsCanonicalizing([$deEdicion->id, $suelto->id], $ids('2026-01-01', '2026-12-31'));
    }

    public function test_solape_acepta_un_solo_extremo(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $deEdicion = $this->crearGrupoConHorarios([], $edicion);
        $suelto = $this->crearGrupoConHorarios();

        $this->assertEqualsCanonicalizing(
            [$deEdicion->id],
            Grupo::queSolapaCon('2026-04-01', null)->pluck('id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$suelto->id],
            Grupo::queSolapaCon(null, '2026-04-01')->pluck('id')->all(),
        );
    }

    public function test_orden_por_inicio_del_periodo_descendente_y_ascendente(): void
    {
        $tarde = $this->crearGrupoConHorarios([], $this->crearEdicionConPeriodo('2026-09-01', '2026-10-01'));
        $medio = $this->crearGrupoConHorarios([], null, ['fecha_inicio' => '2026-06-01', 'fecha_fin' => '2026-06-30']);
        $temprano = $this->crearGrupoConHorarios([], $this->crearEdicionConPeriodo('2026-02-01', '2026-03-01'));

        $this->assertSame(
            [$tarde->id, $medio->id, $temprano->id],
            Grupo::ordenadoPorPeriodo('desc')->pluck('id')->all(),
        );
        $this->assertSame(
            [$temprano->id, $medio->id, $tarde->id],
            Grupo::ordenadoPorPeriodo('asc')->pluck('id')->all(),
        );
    }

    // RF-34 / CA-18: el CRM solo ve estados visibles.
    public function test_grupo_visible_para_crm_segun_su_estado(): void
    {
        $programado = $this->crearGrupoConHorarios();
        $enCurso = $this->crearGrupoConHorarios([], null, ['estado' => EstadoPlanificacion::EnCurso]);
        $this->crearGrupoConHorarios([], null, ['estado' => EstadoPlanificacion::Finalizado]);
        $this->crearGrupoConHorarios([], null, ['estado' => EstadoPlanificacion::Cancelado]);

        $this->assertEqualsCanonicalizing(
            [$programado->id, $enCurso->id],
            Grupo::visibleParaCrm()->pluck('id')->all(),
        );
    }

    public function test_edicion_visible_para_crm_exige_estado_y_programa_visibles(): void
    {
        $visible = $this->edicionDe(EstadoComercial::Abierto, true, EstadoPlanificacion::EnCurso);
        $this->edicionDe(EstadoComercial::Abierto, true, EstadoPlanificacion::Cancelado);
        $this->edicionDe(EstadoComercial::Cerrado, true, EstadoPlanificacion::Programado);
        $this->edicionDe(EstadoComercial::Abierto, false, EstadoPlanificacion::Programado);

        $this->assertSame([$visible->id], Cohorte::visibleParaCrm()->pluck('id')->all());
    }

    // CA-18: el grupo de edición no guarda fechas propias en la API.
    public function test_el_detalle_de_la_api_de_un_grupo_de_edicion_muestra_el_periodo_de_la_edicion(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $grupo = $this->crearGrupoConHorarios([[1, '19:00', '21:00']], $edicion);

        $this->actingAs($this->admin())
            ->getJson("/api/panel/grupos/{$grupo->id}")
            ->assertOk()
            ->assertJsonPath('data.fecha_inicio', null)
            ->assertJsonPath('data.fecha_fin', null)
            ->assertJsonPath('data.periodo_texto', '04/05/2026 – 20/06/2026');
    }

    // CA-18: el detalle muestra el período de la edición.
    public function test_el_detalle_de_un_grupo_de_edicion_muestra_el_periodo_de_la_edicion(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $grupo = $this->crearGrupoConHorarios([], $edicion);

        $this->actingAs($this->admin())->getJson("/api/panel/grupos/{$grupo->id}")
            ->assertOk()
            ->assertJsonPath('data.periodo_texto', '04/05/2026 – 20/06/2026');
    }

    // CA-19: al cambiar las fechas de la edición, el detalle del grupo muestra las nuevas.
    public function test_el_detalle_del_grupo_refleja_el_cambio_de_fechas_de_la_edicion(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $grupo = $this->crearGrupoConHorarios([], $edicion);

        $edicion->update(['fecha_inicio' => '2026-07-01', 'fecha_fin' => '2026-08-15']);

        $this->actingAs($this->admin())->getJson("/api/panel/grupos/{$grupo->id}")
            ->assertOk()
            ->assertJsonPath('data.periodo_texto', '01/07/2026 – 15/08/2026');
    }

    // RF-15 / CA-22: el alta en una edición no pide fechas ni guarda las que lleguen.
    public function test_el_alta_en_una_edicion_no_pide_fechas_y_descarta_las_enviadas(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $modulo = $this->moduloParaGrupo($edicion);

        $this->guardarGrupo($this->datosGrupo($modulo, $edicion))->assertCreated();
        $this->guardarGrupo($this->datosGrupo($modulo, $edicion, ['fecha_inicio' => '2030-01-01', 'fecha_fin' => '2030-01-31']))
            ->assertCreated();

        $this->assertSame(2, Grupo::where('cohorte_id', $edicion->id)->count());
        $this->assertSame(0, Grupo::where('cohorte_id', $edicion->id)->whereNotNull('fecha_inicio')->count());
        $this->assertSame(0, Grupo::where('cohorte_id', $edicion->id)->whereNotNull('fecha_fin')->count());
    }

    // CA-20
    public function test_un_suelto_con_fin_anterior_al_inicio_muestra_el_mensaje_de_fechas(): void
    {
        $modulo = $this->moduloParaGrupo();

        $this->guardarGrupo($this->datosGrupo($modulo, null, ['fecha_inicio' => '2026-04-30', 'fecha_fin' => '2026-04-01']))
            ->assertStatus(422)
            ->assertJsonPath('errors.fecha_fin.0', 'La fecha de fin no puede ser anterior a la de inicio.');

        $this->assertDatabaseCount('grupos', 0);
    }

    // RF-16: un suelto sin fechas se rechaza.
    public function test_un_suelto_sin_fechas_se_rechaza(): void
    {
        $modulo = $this->moduloParaGrupo();
        $datos = $this->datosGrupo($modulo);
        unset($datos['fecha_inicio'], $datos['fecha_fin']);

        $this->guardarGrupo($datos)->assertStatus(422)->assertJsonValidationErrors(['fecha_inicio', 'fecha_fin']);

        $this->assertDatabaseCount('grupos', 0);
    }

    // CA-21 / CA-47: de edición a suelto sin fechas se rechaza y el grupo no cambia.
    public function test_de_edicion_a_suelto_sin_fechas_se_rechaza(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $modulo = $this->moduloParaGrupo($edicion);
        $grupo = $this->grupoNumerado($modulo, $edicion, 1);

        $this->actualizarGrupo($grupo, $this->datosDe($grupo, ['cohorte_id' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['fecha_inicio', 'fecha_fin']);

        $this->assertDatabaseHas('grupos', ['id' => $grupo->id, 'cohorte_id' => $edicion->id]);
    }

    // CA-21 / CA-47: de edición a suelto con fechas, las guarda.
    public function test_de_edicion_a_suelto_con_fechas_las_guarda(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $modulo = $this->moduloParaGrupo($edicion);
        $grupo = $this->grupoNumerado($modulo, $edicion, 1);

        $this->actualizarGrupo($grupo, $this->datosDe($grupo, [
            'cohorte_id' => '',
            'fecha_inicio' => '2026-08-03',
            'fecha_fin' => '2026-08-28',
        ]))->assertOk();

        $this->assertNull($grupo->fresh()->cohorte_id);
        $this->assertSame('03/08/2026 – 28/08/2026', $grupo->fresh()->periodo_texto);
    }

    // CA-22 / CA-47: de suelto a edición deja de pedir fechas.
    public function test_de_suelto_a_edicion_ya_no_pide_fechas_y_usa_el_periodo_de_la_edicion(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $modulo = $this->moduloParaGrupo($edicion);
        $grupo = $this->grupoNumerado($modulo, null, 1);
        $datos = $this->datosDe($grupo, ['cohorte_id' => $edicion->id]);
        unset($datos['fecha_inicio'], $datos['fecha_fin']);

        $this->actualizarGrupo($grupo, $datos)->assertOk();

        $this->assertSame('04/05/2026 – 20/06/2026', $grupo->fresh()->periodo_texto);
    }

    // CA-22 / CA-47: de suelto a edición se descartan las fechas propias aunque lleguen.
    public function test_de_suelto_a_edicion_descarta_las_fechas_propias(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $modulo = $this->moduloParaGrupo($edicion);
        $grupo = $this->grupoNumerado($modulo, null, 1);

        $this->actualizarGrupo($grupo, $this->datosDe($grupo, ['cohorte_id' => $edicion->id]))
            ->assertOk();

        $this->assertDatabaseHas('grupos', ['id' => $grupo->id, 'cohorte_id' => $edicion->id, 'fecha_inicio' => null, 'fecha_fin' => null]);
    }

    // CA-23: la API de opciones ofrece ediciones vigentes.
    public function test_las_opciones_de_planificacion_ofrecen_las_ediciones(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $otra = $this->crearEdicionConPeriodo('2026-09-01', '2026-10-20');

        $res = $this->actingAs($this->admin())->getJson('/api/panel/planificacion/opciones')->assertOk();
        $nombres = collect($res->json('cohortes'))->pluck('nombre')->all();

        $this->assertContains($edicion->nombre, $nombres);
        $this->assertContains($otra->nombre, $nombres);
    }

    private function edicionDe(EstadoComercial $estadoPrograma, bool $conModuloAbierto, EstadoPlanificacion $estado): Cohorte
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20', ['estado' => $estado]);
        $edicion->programa->update(['estado_comercial' => $estadoPrograma]);

        if ($conModuloAbierto) {
            $modulo = Modulo::create([
                'codigo' => 'DEMO-V'.$edicion->id,
                'nombre' => 'DEMO Módulo visible',
                'estado_comercial' => EstadoComercial::Abierto,
            ]);
            $edicion->programa->modulos()->attach($modulo->id, ['orden' => 1]);
        }

        return $edicion;
    }
}
