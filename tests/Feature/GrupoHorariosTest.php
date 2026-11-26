<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Aula;
use App\Models\Grupo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaGruposConHorarios;
use Tests\Concerns\EnviaFormularioDeGrupo;
use Tests\TestCase;

/** RF-18 a RF-22 y RF-24: horarios y modalidad del grupo vía API. */
class GrupoHorariosTest extends TestCase
{
    use CreaGruposConHorarios;
    use EnviaFormularioDeGrupo;
    use RefreshDatabase;

    private const MARTES = 2;

    public function test_un_grupo_programado_sin_horarios_muestra_agrega_al_menos_un_horario(): void
    {
        $datos = $this->datosGrupo($this->moduloParaGrupo());
        unset($datos['horarios']);

        $this->guardarGrupo($datos)
            ->assertStatus(422)
            ->assertJsonPath('errors.horarios.0', 'Agrega al menos un horario.');

        $this->assertDatabaseCount('grupos', 0);
    }

    public function test_un_grupo_en_curso_sin_horarios_tambien_exige_al_menos_un_horario(): void
    {
        $datos = $this->datosGrupo($this->moduloParaGrupo(), null, ['estado' => 'en_curso', 'horarios' => []]);

        $this->guardarGrupo($datos)
            ->assertStatus(422)
            ->assertJsonPath('errors.horarios.0', 'Agrega al menos un horario.');

        $this->assertDatabaseCount('grupos', 0);
    }

    public function test_un_grupo_cancelado_o_finalizado_sin_horarios_se_guarda(): void
    {
        foreach (['cancelado', 'finalizado'] as $estado) {
            $datos = $this->datosGrupo($this->moduloParaGrupo(), null, ['estado' => $estado]);
            unset($datos['horarios']);

            $this->guardarGrupo($datos)->assertCreated();
        }

        $this->assertSame(2, Grupo::count());
        $this->assertDatabaseCount('horarios_grupo', 0);
    }

    public function test_un_grupo_cancelado_con_horarios_invalidos_se_rechaza(): void
    {
        $datos = $this->datosGrupo($this->moduloParaGrupo(), null, [
            'estado' => 'cancelado',
            'horarios' => [$this->horario(self::MARTES, '21:00', '19:00')],
        ]);

        $this->guardarGrupo($datos)
            ->assertStatus(422)
            ->assertJsonValidationErrors('horarios.0.hora_fin');
    }

    public function test_un_programado_existente_sin_horarios_se_ve_normal_y_se_exige_al_editar(): void
    {
        $grupo = $this->crearGrupoConHorarios([], null, ['docente_id' => $this->docenteTitular()->id]);

        $this->actingAs($this->admin())->getJson("/api/panel/grupos/{$grupo->id}")->assertOk();

        $datos = $this->datosDe($grupo);
        unset($datos['horarios']);
        $this->actualizarGrupo($grupo, $datos)
            ->assertStatus(422)
            ->assertJsonPath('errors.horarios.0', 'Agrega al menos un horario.');
    }

    public function test_al_editar_un_grupo_a_cancelado_no_se_exigen_horarios(): void
    {
        $grupo = $this->crearGrupoConHorarios([], null, ['docente_id' => $this->docenteTitular()->id]);
        $datos = $this->datosDe($grupo, ['estado' => 'cancelado']);
        unset($datos['horarios']);

        $this->actualizarGrupo($grupo, $datos)->assertOk();

        $this->assertDatabaseHas('grupos', ['id' => $grupo->id, 'estado' => 'cancelado']);
    }

    public function test_varios_horarios_incluso_el_mismo_dia_se_guardan_y_se_reabren(): void
    {
        $modulo = $this->moduloParaGrupo();

        $this->guardarGrupo($this->datosGrupo($modulo, null, ['horarios' => [
            $this->horario(1, '19:00', '21:00'),
            $this->horario(3, '19:00', '21:00'),
            $this->horario(1, '08:00', '10:00'),
        ]]))->assertCreated();

        $grupo = Grupo::where('modulo_id', $modulo->id)->firstOrFail();
        $rangos = $grupo->horarios->map(fn ($h) => $h->dia_semana->value.' '.$h->rango)->all();
        $this->assertSame(['1 08:00–10:00', '1 19:00–21:00', '3 19:00–21:00'], $rangos);

        $res = $this->actingAs($this->admin())->getJson("/api/panel/grupos/{$grupo->id}")->assertOk();
        $this->assertCount(3, $res->json('data.horarios'));
    }

    public function test_quitar_y_agregar_horarios_reemplaza_los_anteriores(): void
    {
        $grupo = $this->crearGrupoConHorarios([[1, '19:00', '21:00'], [3, '19:00', '21:00']], null, ['docente_id' => $this->docenteTitular()->id]);

        $this->actualizarGrupo($grupo, $this->datosDe($grupo, ['horarios' => [
            $this->horario(3, '19:00', '21:00'),
            $this->horario(5, '10:00', '12:00'),
        ]]))->assertOk();

        $rangos = $grupo->fresh()->horarios->map(fn ($h) => $h->dia_semana->value.' '.$h->rango)->all();
        $this->assertSame(['3 19:00–21:00', '5 10:00–12:00'], $rangos);
        $this->assertDatabaseCount('horarios_grupo', 2);
    }

    public function test_mas_de_14_horarios_se_rechazan_y_14_se_aceptan(): void
    {
        $catorce = [];
        foreach (range(1, 7) as $dia) {
            $catorce[] = $this->horario($dia, '08:00', '10:00');
            $catorce[] = $this->horario($dia, '10:00', '12:00');
        }

        $this->guardarGrupo($this->datosGrupo($this->moduloParaGrupo(), null, ['horarios' => [...$catorce, $this->horario(1, '14:00', '16:00')]]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('horarios');
        $this->assertDatabaseCount('grupos', 0);

        $this->guardarGrupo($this->datosGrupo($this->moduloParaGrupo(), null, ['horarios' => $catorce]))
            ->assertCreated();
        $this->assertDatabaseCount('horarios_grupo', 14);
    }

    public function test_una_fila_con_dia_u_hora_invalidos_se_rechaza(): void
    {
        $this->guardarGrupo($this->datosGrupo($this->moduloParaGrupo(), null, ['horarios' => [
            ['dia_semana' => 9, 'hora_inicio' => '25:99', 'hora_fin' => ''],
        ]]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['horarios.0.dia_semana', 'horarios.0.hora_inicio', 'horarios.0.hora_fin']);

        $this->assertDatabaseCount('grupos', 0);
    }

    public function test_un_horario_de_martes_de_21_a_19_muestra_que_el_fin_debe_ser_posterior(): void
    {
        $res = $this->guardarGrupo($this->datosGrupo($this->moduloParaGrupo(), null, ['horarios' => [
            $this->horario(self::MARTES, '21:00', '19:00'),
        ]]))->assertStatus(422);

        $this->assertSame('En martes, la hora de fin debe ser posterior a la de inicio.', $res->json('errors')['horarios.0.hora_fin'][0]);
        $this->assertDatabaseCount('grupos', 0);
    }

    public function test_un_horario_con_fin_igual_al_inicio_se_rechaza(): void
    {
        $res = $this->guardarGrupo($this->datosGrupo($this->moduloParaGrupo(), null, ['horarios' => [
            $this->horario(self::MARTES, '19:00', '19:00'),
        ]]))->assertStatus(422);

        $this->assertSame('En martes, la hora de fin debe ser posterior a la de inicio.', $res->json('errors')['horarios.0.hora_fin'][0]);
    }

    public function test_el_error_de_fin_aparece_antes_que_el_de_solape(): void
    {
        $res = $this->guardarGrupo($this->datosGrupo($this->moduloParaGrupo(), null, ['horarios' => [
            $this->horario(1, '18:00', '20:00'),
            $this->horario(1, '19:00', '21:00'),
            $this->horario(self::MARTES, '21:00', '19:00'),
        ]]))->assertStatus(422);

        $claves = array_keys($res->json('errors'));
        $this->assertSame('horarios.2.hora_fin', $claves[0]);
        $this->assertContains('horarios.1.hora_inicio', $claves);
    }

    public function test_dos_horarios_del_martes_que_se_solapan_muestran_el_mensaje_de_solape(): void
    {
        $res = $this->guardarGrupo($this->datosGrupo($this->moduloParaGrupo(), null, ['horarios' => [
            $this->horario(self::MARTES, '18:00', '20:00'),
            $this->horario(self::MARTES, '19:00', '21:00'),
        ]]))->assertStatus(422);

        $this->assertSame('En martes, los horarios se solapan.', $res->json('errors')['horarios.1.hora_inicio'][0]);
        $this->assertDatabaseCount('grupos', 0);
    }

    public function test_el_solape_se_marca_en_la_fila_posterior_aunque_lleguen_desordenadas(): void
    {
        $res = $this->guardarGrupo($this->datosGrupo($this->moduloParaGrupo(), null, ['horarios' => [
            $this->horario(self::MARTES, '19:00', '21:00'),
            $this->horario(self::MARTES, '18:00', '20:00'),
        ]]))->assertStatus(422);

        $this->assertSame('En martes, los horarios se solapan.', $res->json('errors')['horarios.0.hora_inicio'][0]);
    }

    public function test_varios_solapes_del_mismo_dia_muestran_un_solo_mensaje(): void
    {
        $res = $this->guardarGrupo($this->datosGrupo($this->moduloParaGrupo(), null, ['horarios' => [
            $this->horario(self::MARTES, '18:00', '20:00'),
            $this->horario(self::MARTES, '19:00', '21:00'),
            $this->horario(self::MARTES, '19:30', '22:00'),
        ]]))->assertStatus(422);

        $mensajes = collect($res->json('errors'))->flatten()
            ->filter(fn ($m) => $m === 'En martes, los horarios se solapan.');
        $this->assertCount(1, $mensajes);
    }

    public function test_dos_horarios_que_se_tocan_se_aceptan(): void
    {
        $this->guardarGrupo($this->datosGrupo($this->moduloParaGrupo(), null, ['horarios' => [
            $this->horario(self::MARTES, '18:00', '19:00'),
            $this->horario(self::MARTES, '19:00', '20:00'),
        ]]))->assertCreated();

        $this->assertDatabaseCount('horarios_grupo', 2);
    }

    public function test_el_mismo_rango_en_dias_distintos_no_se_solapa(): void
    {
        $this->guardarGrupo($this->datosGrupo($this->moduloParaGrupo(), null, ['horarios' => [
            $this->horario(2, '18:00', '20:00'),
            $this->horario(3, '18:00', '20:00'),
        ]]))->assertCreated();

        $this->assertDatabaseCount('horarios_grupo', 2);
    }

    public function test_dos_grupos_del_mismo_docente_con_el_mismo_horario_se_aceptan(): void
    {
        $modulo = $this->moduloParaGrupo();
        $otroModulo = $this->moduloParaGrupo();
        $horarios = [$this->horario(self::MARTES, '18:00', '20:00')];

        $this->guardarGrupo($this->datosGrupo($modulo, null, ['horarios' => $horarios]))->assertCreated();
        $this->guardarGrupo($this->datosGrupo($modulo, null, ['horarios' => $horarios]))->assertCreated();
        $this->guardarGrupo($this->datosGrupo($otroModulo, null, ['horarios' => $horarios]))->assertCreated();

        $this->assertDatabaseCount('grupos', 3);
        $this->assertSame(1, Grupo::select('docente_id')->distinct()->count('docente_id'));
    }

    public function test_dos_grupos_presenciales_con_la_misma_aula_y_horario_se_aceptan(): void
    {
        $aula = Aula::create(['nombre' => 'DEMO Aula 1', 'capacidad' => 40]);
        $horarios = [$this->horario(self::MARTES, '18:00', '20:00')];
        $presencial = ['modalidad' => 'presencial', 'aula_id' => $aula->id, 'horarios' => $horarios];

        $this->guardarGrupo($this->datosGrupo($this->moduloParaGrupo(), null, $presencial))->assertCreated();
        $this->guardarGrupo($this->datosGrupo($this->moduloParaGrupo(), null, $presencial))->assertCreated();

        $this->assertDatabaseCount('grupos', 2);
    }

    public function test_un_grupo_presencial_sin_aula_se_rechaza_pidiendo_el_aula(): void
    {
        $this->guardarGrupo($this->datosGrupo($this->moduloParaGrupo(), null, ['modalidad' => 'presencial']))
            ->assertStatus(422)
            ->assertJsonPath('errors.aula_id.0', 'Debe asignar un aula física para grupos en modalidad presencial.');

        $this->assertDatabaseCount('grupos', 0);
    }

    public function test_un_grupo_presencial_con_aula_se_guarda(): void
    {
        $aula = Aula::create(['nombre' => 'DEMO Aula 2', 'capacidad' => 40]);

        $this->guardarGrupo($this->datosGrupo($this->moduloParaGrupo(), null, ['modalidad' => 'presencial', 'aula_id' => $aula->id]))
            ->assertCreated();

        $this->assertDatabaseHas('grupos', ['modalidad' => 'presencial', 'aula_id' => $aula->id]);
    }

    public function test_un_grupo_virtual_se_guarda_sin_aula_aunque_se_envie(): void
    {
        $aula = Aula::create(['nombre' => 'DEMO Aula 3', 'capacidad' => 40]);

        $this->guardarGrupo($this->datosGrupo($this->moduloParaGrupo(), null, ['modalidad' => 'virtual', 'aula_id' => $aula->id]))
            ->assertCreated();

        $this->assertDatabaseHas('grupos', ['modalidad' => 'virtual', 'aula_id' => null]);
    }

    public function test_un_grupo_mixto_se_guarda_con_o_sin_aula(): void
    {
        $aula = Aula::create(['nombre' => 'DEMO Aula 4', 'capacidad' => 40]);

        $this->guardarGrupo($this->datosGrupo($this->moduloParaGrupo(), null, ['modalidad' => 'mixta']))->assertCreated();
        $this->guardarGrupo($this->datosGrupo($this->moduloParaGrupo(), null, ['modalidad' => 'mixta', 'aula_id' => $aula->id]))->assertCreated();

        $this->assertDatabaseHas('grupos', ['modalidad' => 'mixta', 'aula_id' => null]);
        $this->assertDatabaseHas('grupos', ['modalidad' => 'mixta', 'aula_id' => $aula->id]);
    }

    public function test_cada_error_de_horario_se_asocia_a_su_indice_exacto(): void
    {
        $res = $this->guardarGrupo($this->datosGrupo($this->moduloParaGrupo(), null, ['horarios' => [
            $this->horario(1, '18:00', '20:00'),
            $this->horario(1, '19:00', '21:00'),
            $this->horario(self::MARTES, '21:00', '19:00'),
        ]]))->assertStatus(422);

        $errores = $res->json('errors');
        $this->assertArrayNotHasKey('horarios.0.hora_inicio', $errores);
        $this->assertArrayNotHasKey('horarios.0.hora_fin', $errores);
        $this->assertSame('En lunes, los horarios se solapan.', $errores['horarios.1.hora_inicio'][0]);
        $this->assertSame('En martes, la hora de fin debe ser posterior a la de inicio.', $errores['horarios.2.hora_fin'][0]);
    }

    public function test_un_docente_no_puede_crear_ni_editar_grupos(): void
    {
        $docente = User::factory()->create(['rol' => Rol::Docente, 'estado' => 'activo']);
        $grupo = $this->crearGrupoConHorarios([[1, '19:00', '21:00']]);
        $datos = $this->datosGrupo($this->moduloParaGrupo());

        $this->actingAs($docente)->postJson('/api/panel/grupos', $datos)->assertForbidden();
        $this->actingAs($docente)->putJson("/api/panel/grupos/{$grupo->id}", $this->datosDe($grupo))->assertForbidden();

        $this->assertDatabaseCount('grupos', 1);
    }
}
