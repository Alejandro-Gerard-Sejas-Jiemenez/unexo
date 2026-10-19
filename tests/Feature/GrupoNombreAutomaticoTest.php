<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Modulo;
use App\Models\User;
use App\Services\GrupoService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Concerns\CreaGruposConHorarios;
use Tests\Concerns\EnviaFormularioDeGrupo;
use Tests\TestCase;

/** RF-11 a RF-14: nombre automático "Grupo N" por contexto (módulo y edición, o sueltos del módulo). */
class GrupoNombreAutomaticoTest extends TestCase
{
    use CreaGruposConHorarios;
    use EnviaFormularioDeGrupo;
    use RefreshDatabase;

    // CA-11
    public function test_dos_grupos_del_mismo_modulo_y_edicion_se_llaman_grupo_1_y_grupo_2_sin_enviar_nombre(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $modulo = $this->moduloParaGrupo($edicion);

        $this->guardarGrupo($this->datosGrupo($modulo, $edicion))->assertSessionHasNoErrors();
        $this->guardarGrupo($this->datosGrupo($modulo, $edicion))->assertSessionHasNoErrors();

        $this->assertSame(['Grupo 1', 'Grupo 2'], $this->nombresEnContexto($modulo, $edicion));
        $this->assertSame([1, 2], Grupo::where('modulo_id', $modulo->id)->orderBy('numero')->pluck('numero')->all());
    }

    // CA-11 / RF-11: el contexto sin edición numera igual.
    public function test_dos_grupos_sueltos_del_mismo_modulo_se_llaman_grupo_1_y_grupo_2(): void
    {
        $modulo = $this->moduloParaGrupo();

        $this->guardarGrupo($this->datosGrupo($modulo))->assertSessionHasNoErrors();
        $this->guardarGrupo($this->datosGrupo($modulo))->assertSessionHasNoErrors();

        $this->assertSame(['Grupo 1', 'Grupo 2'], $this->nombresEnContexto($modulo, null));
    }

    // RF-11: una petición que envía nombre lo ignora.
    public function test_el_nombre_enviado_en_la_peticion_se_ignora(): void
    {
        $modulo = $this->moduloParaGrupo();

        $this->guardarGrupo($this->datosGrupo($modulo, null, ['nombre' => 'Mi grupo especial']))
            ->assertSessionHasNoErrors();

        $this->assertSame(['Grupo 1'], $this->nombresEnContexto($modulo, null));
        $this->assertDatabaseMissing('grupos', ['nombre' => 'Mi grupo especial']);
    }

    // CA-12
    public function test_un_grupo_cancelado_cuenta_para_el_numero_siguiente(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $modulo = $this->moduloParaGrupo($edicion);
        $this->grupoNumerado($modulo, $edicion, 1);
        $this->grupoNumerado($modulo, $edicion, 2, ['estado' => 'cancelado']);

        $this->guardarGrupo($this->datosGrupo($modulo, $edicion))->assertSessionHasNoErrors();

        $this->assertSame(['Grupo 1', 'Grupo 2', 'Grupo 3'], $this->nombresEnContexto($modulo, $edicion));
    }

    // CA-13
    public function test_un_grupo_suelto_no_cuenta_para_la_edicion(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $modulo = $this->moduloParaGrupo($edicion);
        $this->grupoNumerado($modulo, null, 1);

        $this->guardarGrupo($this->datosGrupo($modulo, $edicion))->assertSessionHasNoErrors();

        $this->assertSame(['Grupo 1'], $this->nombresEnContexto($modulo, $edicion));
    }

    // CA-13 (recíproco)
    public function test_un_grupo_de_edicion_no_cuenta_para_los_sueltos(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $modulo = $this->moduloParaGrupo($edicion);
        $this->grupoNumerado($modulo, $edicion, 1);

        $this->guardarGrupo($this->datosGrupo($modulo))->assertSessionHasNoErrors();

        $this->assertSame(['Grupo 1'], $this->nombresEnContexto($modulo, null));
    }

    // RF-13: otro módulo y otra edición del mismo módulo son contextos distintos.
    public function test_otro_modulo_y_otra_edicion_son_contextos_distintos(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $otraEdicion = $this->crearEdicionConPeriodo('2026-09-01', '2026-10-20', ['programa_id' => $edicion->programa_id]);
        $modulo = $this->moduloParaGrupo($edicion);
        $otroModulo = $this->moduloParaGrupo($edicion);
        $this->grupoNumerado($modulo, $otraEdicion, 1);
        $this->grupoNumerado($otroModulo, $edicion, 1);

        $this->guardarGrupo($this->datosGrupo($modulo, $edicion))->assertSessionHasNoErrors();

        $this->assertSame(['Grupo 1'], $this->nombresEnContexto($modulo, $edicion));
    }

    // CA-43
    public function test_un_nombre_con_otro_formato_se_ignora_al_calcular_el_siguiente(): void
    {
        $modulo = $this->moduloParaGrupo();
        $this->grupoNumerado($modulo, null, 1, ['nombre' => 'Grupo A', 'numero' => null]);

        $this->guardarGrupo($this->datosGrupo($modulo))->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing(['Grupo A', 'Grupo 1'], $this->nombresEnContexto($modulo, null));
        $this->assertDatabaseHas('grupos', ['modulo_id' => $modulo->id, 'nombre' => 'Grupo 1', 'numero' => 1]);
    }

    // CA-14
    public function test_cambiar_el_docente_conserva_el_nombre(): void
    {
        $modulo = $this->moduloParaGrupo();
        $grupo = $this->grupoNumerado($modulo, null, 2);
        $otroDocente = User::factory()->create(['rol' => Rol::Docente, 'estado' => 'activo']);

        $this->actualizarGrupo($grupo, $this->datosDe($grupo, ['docente_id' => $otroDocente->id]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('grupos', ['id' => $grupo->id, 'docente_id' => $otroDocente->id, 'nombre' => 'Grupo 2', 'numero' => 2]);
    }

    // CA-15
    public function test_al_mover_a_una_edicion_con_ese_nombre_recibe_el_siguiente_numero(): void
    {
        $edicionA = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $edicionB = $this->crearEdicionConPeriodo('2026-09-01', '2026-10-20', ['programa_id' => $edicionA->programa_id]);
        $modulo = $this->moduloParaGrupo($edicionA);
        $grupo = $this->grupoNumerado($modulo, $edicionA, 1);
        $this->grupoNumerado($modulo, $edicionB, 1);

        $this->actualizarGrupo($grupo, $this->datosDe($grupo, ['cohorte_id' => $edicionB->id]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('grupos', ['id' => $grupo->id, 'cohorte_id' => $edicionB->id, 'nombre' => 'Grupo 2', 'numero' => 2]);
    }

    // CA-15 (rama que conserva)
    public function test_al_mover_a_una_edicion_sin_ese_nombre_lo_conserva(): void
    {
        $edicionA = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $edicionB = $this->crearEdicionConPeriodo('2026-09-01', '2026-10-20', ['programa_id' => $edicionA->programa_id]);
        $modulo = $this->moduloParaGrupo($edicionA);
        $grupo = $this->grupoNumerado($modulo, $edicionA, 3);
        $this->grupoNumerado($modulo, $edicionB, 1);

        $this->actualizarGrupo($grupo, $this->datosDe($grupo, ['cohorte_id' => $edicionB->id]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('grupos', ['id' => $grupo->id, 'cohorte_id' => $edicionB->id, 'nombre' => 'Grupo 3', 'numero' => 3]);
    }

    // CA-44: pasar de edición a suelto, sin choque.
    public function test_al_pasar_de_edicion_a_suelto_conserva_el_nombre_si_no_existe(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $modulo = $this->moduloParaGrupo($edicion);
        $grupo = $this->grupoNumerado($modulo, $edicion, 1);

        $this->actualizarGrupo($grupo, $this->datosDe($grupo, [
            'cohorte_id' => '',
            'fecha_inicio' => '2026-07-01',
            'fecha_fin' => '2026-07-31',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('grupos', ['id' => $grupo->id, 'cohorte_id' => null, 'nombre' => 'Grupo 1', 'numero' => 1]);
    }

    // CA-44: pasar de edición a suelto, con choque.
    public function test_al_pasar_de_edicion_a_suelto_renumera_si_ya_existe(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $modulo = $this->moduloParaGrupo($edicion);
        $grupo = $this->grupoNumerado($modulo, $edicion, 1);
        $this->grupoNumerado($modulo, null, 1);

        $this->actualizarGrupo($grupo, $this->datosDe($grupo, [
            'cohorte_id' => '',
            'fecha_inicio' => '2026-07-01',
            'fecha_fin' => '2026-07-31',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('grupos', ['id' => $grupo->id, 'cohorte_id' => null, 'nombre' => 'Grupo 2', 'numero' => 2]);
    }

    // CA-44: el caso inverso (deja de ser suelto) sigue la misma regla.
    public function test_al_pasar_de_suelto_a_edicion_renumera_si_ya_existe(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $modulo = $this->moduloParaGrupo($edicion);
        $grupo = $this->grupoNumerado($modulo, null, 1);
        $this->grupoNumerado($modulo, $edicion, 1);

        $this->actualizarGrupo($grupo, $this->datosDe($grupo, ['cohorte_id' => $edicion->id]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('grupos', ['id' => $grupo->id, 'cohorte_id' => $edicion->id, 'nombre' => 'Grupo 2', 'numero' => 2]);
    }

    // Supuesto P-4: cambiar de módulo cambia el contexto.
    public function test_al_cambiar_de_modulo_renumera_si_el_nombre_existe_en_el_modulo_nuevo(): void
    {
        $moduloA = $this->moduloParaGrupo();
        $moduloB = $this->moduloParaGrupo();
        $grupo = $this->grupoNumerado($moduloA, null, 1);
        $this->grupoNumerado($moduloB, null, 1);

        $this->actualizarGrupo($grupo, $this->datosDe($grupo, ['modulo_id' => $moduloB->id]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('grupos', ['id' => $grupo->id, 'modulo_id' => $moduloB->id, 'nombre' => 'Grupo 2', 'numero' => 2]);
    }

    // Supuesto P-4 (rama que conserva)
    public function test_al_cambiar_de_modulo_conserva_el_nombre_si_no_existe_en_el_modulo_nuevo(): void
    {
        $moduloA = $this->moduloParaGrupo();
        $moduloB = $this->moduloParaGrupo();
        $grupo = $this->grupoNumerado($moduloA, null, 1);

        $this->actualizarGrupo($grupo, $this->datosDe($grupo, ['modulo_id' => $moduloB->id]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('grupos', ['id' => $grupo->id, 'modulo_id' => $moduloB->id, 'nombre' => 'Grupo 1', 'numero' => 1]);
    }

    // CA-16: un competidor toma el mismo número entre el cálculo y el guardado.
    public function test_dos_altas_simultaneas_de_la_misma_edicion_reciben_nombres_distintos(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $modulo = $this->moduloParaGrupo($edicion);
        $contador = $this->simularCompetidorConfirmado($modulo, $edicion);

        $this->guardarGrupo($this->datosGrupo($modulo, $edicion))->assertSessionHasNoErrors();

        $this->assertGreaterThanOrEqual(2, $contador->llamadas);

        $this->assertSame(['Grupo 1', 'Grupo 2'], $this->nombresEnContexto($modulo, $edicion));
        $this->assertSame(2, Grupo::where('modulo_id', $modulo->id)->count());
    }

    // CA-45
    public function test_dos_altas_simultaneas_de_sueltos_del_mismo_modulo_reciben_nombres_distintos(): void
    {
        $modulo = $this->moduloParaGrupo();
        $contador = $this->simularCompetidorConfirmado($modulo, null);

        $this->guardarGrupo($this->datosGrupo($modulo))->assertSessionHasNoErrors();

        $this->assertGreaterThanOrEqual(2, $contador->llamadas);

        $this->assertSame(['Grupo 1', 'Grupo 2'], $this->nombresEnContexto($modulo, null));
        $this->assertSame(2, Grupo::where('modulo_id', $modulo->id)->count());
    }

    // RF-13 / D7: el índice único respalda la regla en ambos contextos.
    public function test_el_indice_unico_rechaza_el_mismo_numero_en_el_mismo_contexto(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $modulo = $this->moduloParaGrupo($edicion);
        $this->grupoNumerado($modulo, $edicion, 1);
        $this->grupoNumerado($modulo, null, 1);

        $this->assertThrows(fn () => $this->grupoNumerado($modulo, $edicion, 1, ['nombre' => 'Grupo 1 bis']), QueryException::class);
        $this->assertThrows(fn () => $this->grupoNumerado($modulo, null, 1, ['nombre' => 'Grupo 1 bis']), QueryException::class);

        // El mismo número en otro módulo sí es válido.
        $this->grupoNumerado($this->moduloParaGrupo(), null, 1);
        $this->assertSame(1, Grupo::where('modulo_id', $modulo->id)->whereNull('cohorte_id')->count());
    }

    // RF-11 / D9: la apertura asistida usa el mismo nombre automático.
    public function test_la_apertura_asistida_nombra_los_grupos_con_el_nombre_automatico(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $moduloA = $this->moduloParaGrupo($edicion);
        $moduloB = $this->moduloParaGrupo($edicion);
        $this->grupoNumerado($moduloB, null, 1); // un suelto no cuenta para la edición

        $this->actingAs($this->admin())
            ->post(route('cohortes.aperturar-grupos', $edicion), [
                'docente_id' => $this->docenteTitular()->id,
                'modalidad' => 'virtual',
            ])
            ->assertRedirect(route('cohortes.show', $edicion));

        $this->assertSame(['Grupo 1'], $this->nombresEnContexto($moduloA, $edicion));
        $this->assertSame(['Grupo 1'], $this->nombresEnContexto($moduloB, $edicion));
    }

    /**
     * Fiel a producción: el competidor "Grupo 1" ya está confirmado antes del primer intento, y el servicio
     * calcula en ese intento un número obsoleto (1) porque lo leyó antes de que el competidor confirmara.
     * Devuelve un contador de llamadas a siguienteNumero; con el reintento debe ser al menos 2.
     */
    private function simularCompetidorConfirmado(Modulo $modulo, ?Cohorte $edicion): \stdClass
    {
        $this->grupoNumerado($modulo, $edicion, 1);

        $real = new GrupoService;
        $contador = new \stdClass;
        $contador->llamadas = 0;

        $servicio = Mockery::mock(GrupoService::class)->makePartial();
        $servicio->shouldReceive('siguienteNumero')->andReturnUsing(
            function (string $moduloId, ?string $cohorteId) use ($real, $contador): int {
                $contador->llamadas++;

                return $contador->llamadas === 1 ? 1 : $real->siguienteNumero($moduloId, $cohorteId);
            }
        );
        $this->app->instance(GrupoService::class, $servicio);

        return $contador;
    }
}
