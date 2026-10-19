<?php

namespace Tests\Feature;

use App\Models\Aula;
use App\Models\Grupo;
use App\Services\GrupoService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreaGruposConHorarios;
use Tests\Concerns\EnviaFormularioDeGrupo;
use Tests\TestCase;

/** GrupoService aislado del controlador: nombre, período, modalidad y horarios (spec 009, D6 a D10). */
class GrupoServiceTest extends TestCase
{
    use CreaGruposConHorarios;
    use EnviaFormularioDeGrupo;
    use RefreshDatabase;

    private function servicio(): GrupoService
    {
        return app(GrupoService::class);
    }

    public function test_crear_asigna_nombre_y_numero_correlativos_y_guarda_horarios_con_segundos(): void
    {
        $modulo = $this->moduloParaGrupo();

        $primero = $this->servicio()->crear($this->datosGrupo($modulo));
        $segundo = $this->servicio()->crear($this->datosGrupo($modulo, null, ['nombre' => 'Otro']));

        $this->assertSame(['Grupo 1', 1], [$primero->nombre, $primero->numero]);
        $this->assertSame(['Grupo 2', 2], [$segundo->nombre, $segundo->numero]);
        $this->assertDatabaseHas('horarios_grupo', ['grupo_id' => $primero->id, 'hora_inicio' => '19:00:00', 'hora_fin' => '21:00:00']);
    }

    public function test_crear_con_edicion_descarta_las_fechas_y_sin_edicion_las_conserva(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $modulo = $this->moduloParaGrupo($edicion);

        $deEdicion = $this->servicio()->crear($this->datosGrupo($modulo, $edicion, ['fecha_inicio' => '2030-01-01', 'fecha_fin' => '2030-01-31']));
        $suelto = $this->servicio()->crear($this->datosGrupo($modulo));

        $this->assertNull($deEdicion->fresh()->fecha_inicio);
        $this->assertNull($deEdicion->fresh()->fecha_fin);
        $this->assertSame('2026-04-01', $suelto->fresh()->fecha_inicio->toDateString());
    }

    public function test_virtual_descarta_el_aula_y_presencial_descarta_el_enlace(): void
    {
        $aula = Aula::create(['nombre' => 'DEMO Aula', 'capacidad' => 30]);

        $virtual = $this->servicio()->crear($this->datosGrupo($this->moduloParaGrupo(), null, [
            'aula_id' => $aula->id, 'enlace_videoconferencia' => 'https://demo.test/sala',
        ]));
        $presencial = $this->servicio()->crear($this->datosGrupo($this->moduloParaGrupo(), null, [
            'modalidad' => 'presencial', 'aula_id' => $aula->id, 'enlace_videoconferencia' => 'https://demo.test/sala',
        ]));

        $this->assertNull($virtual->fresh()->aula_id);
        $this->assertSame('https://demo.test/sala', $virtual->fresh()->enlace_videoconferencia);
        $this->assertSame($aula->id, $presencial->fresh()->aula_id);
        $this->assertNull($presencial->fresh()->enlace_videoconferencia);
    }

    public function test_cupo_minimo_cero_se_guarda_como_vacio(): void
    {
        $grupo = $this->servicio()->crear($this->datosGrupo($this->moduloParaGrupo(), null, ['cupo_minimo' => '0']));

        $this->assertNull($grupo->fresh()->cupo_minimo);
    }

    public function test_sincronizar_horarios_reemplaza_todas_las_filas(): void
    {
        $grupo = $this->servicio()->crear($this->datosGrupo($this->moduloParaGrupo()));

        $this->servicio()->sincronizarHorarios($grupo, [$this->horario(2, '08:00', '10:00'), $this->horario(3, '09:30', '11:00')]);

        $this->assertSame(['08:00–10:00', '09:30–11:00'], $grupo->horarios()->get()->pluck('rango')->all());
    }

    public function test_actualizar_sin_horarios_en_los_datos_no_los_toca(): void
    {
        $grupo = $this->servicio()->crear($this->datosGrupo($this->moduloParaGrupo()));
        $datos = $this->datosDe($grupo);
        unset($datos['horarios']);

        $this->servicio()->actualizar($grupo, $datos);

        $this->assertSame(1, $grupo->horarios()->count());
    }

    public function test_actualizar_sin_cambiar_el_contexto_no_toca_nombre_ni_numero(): void
    {
        $modulo = $this->moduloParaGrupo();
        $grupo = $this->grupoNumerado($modulo, null, 4);

        $this->servicio()->actualizar($grupo, $this->datosDe($grupo, ['nombre' => 'Intruso', 'numero' => 9]));

        $this->assertDatabaseHas('grupos', ['id' => $grupo->id, 'nombre' => 'Grupo 4', 'numero' => 4]);
    }

    public function test_actualizar_con_nombre_de_otro_formato_al_cambiar_de_contexto_lo_conserva_sin_numero(): void
    {
        $moduloA = $this->moduloParaGrupo();
        $moduloB = $this->moduloParaGrupo();
        $grupo = $this->grupoNumerado($moduloA, null, 1, ['nombre' => 'Grupo A', 'numero' => null]);

        $this->servicio()->actualizar($grupo, $this->datosDe($grupo, ['modulo_id' => $moduloB->id]));

        $this->assertDatabaseHas('grupos', ['id' => $grupo->id, 'modulo_id' => $moduloB->id, 'nombre' => 'Grupo A', 'numero' => null]);
    }

    public function test_siguiente_numero_cuenta_cancelados_y_ignora_otros_contextos(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $modulo = $this->moduloParaGrupo($edicion);
        $this->grupoNumerado($modulo, $edicion, 3, ['estado' => 'cancelado']);
        $this->grupoNumerado($modulo, null, 7);

        $this->assertSame(4, $this->servicio()->siguienteNumero($modulo->id, $edicion->id));
        $this->assertSame(8, $this->servicio()->siguienteNumero($modulo->id, null));
        $this->assertSame(1, $this->servicio()->siguienteNumero($this->moduloParaGrupo()->id, null));
    }

    public function test_numero_del_nombre_solo_reconoce_el_formato_exacto(): void
    {
        $this->assertSame(12, $this->servicio()->numeroDelNombre('Grupo 12'));
        foreach (['Grupo A', 'Grupo 01', 'Grupo 0', 'grupo 1', 'Grupo 1 bis', ' Grupo 1', "Grupo 1\n", 'Grupo 70000'] as $nombre) {
            $this->assertNull($this->servicio()->numeroDelNombre($nombre), $nombre);
        }
    }

    public function test_un_choque_de_unicidad_repite_la_operacion_completa_y_la_segunda_vez_guarda(): void
    {
        $modulo = $this->moduloParaGrupo();
        $intentos = 0;
        Grupo::creating(function () use (&$intentos) {
            if (++$intentos === 1) {
                throw new UniqueConstraintViolationException('sqlite', 'insert', [], new \Exception('duplicado'));
            }
        });

        $grupo = $this->servicio()->crear($this->datosGrupo($modulo));

        $this->assertSame(2, $intentos);
        $this->assertSame(1, Grupo::where('modulo_id', $modulo->id)->count());
        $this->assertSame(1, $grupo->horarios()->count());
    }

    public function test_si_la_carrera_persiste_se_agotan_los_intentos_con_un_error_de_validacion(): void
    {
        $modulo = $this->moduloParaGrupo();
        $intentos = 0;
        Grupo::creating(function () use (&$intentos) {
            $intentos++;
            throw new UniqueConstraintViolationException('sqlite', 'insert', [], new \Exception('duplicado'));
        });

        try {
            $this->servicio()->crear($this->datosGrupo($modulo));
            $this->fail('Debía lanzar ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame('No se pudo asignar el nombre del grupo. Intenta de nuevo.', $e->errors()['modulo_id'][0]);
        }

        $this->assertSame(5, $intentos);
        $this->assertDatabaseCount('grupos', 0);
    }
}
