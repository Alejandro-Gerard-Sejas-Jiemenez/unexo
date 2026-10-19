<?php

namespace Tests\Feature;

use App\Http\Requests\StoreGrupoRequest;
use App\Models\Aula;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\CreaGruposConHorarios;
use Tests\Concerns\EnviaFormularioDeGrupo;
use Tests\TestCase;

/** Reglas de StoreGrupoRequest probadas sin pasar por el controlador (ruta de prueba que solo valida). */
class GrupoRequestTest extends TestCase
{
    use CreaGruposConHorarios;
    use EnviaFormularioDeGrupo;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->post('/_prueba/grupo-request', fn (StoreGrupoRequest $request) => response()->json($request->validated()));
    }

    private function validar(array $datos)
    {
        return $this->postJson('/_prueba/grupo-request', $datos);
    }

    public function test_datos_validos_pasan_y_el_nombre_no_se_valida(): void
    {
        $respuesta = $this->validar($this->datosGrupo($this->moduloParaGrupo(), null, ['nombre' => 'X']));

        $respuesta->assertOk()->assertJsonMissing(['nombre' => 'X']);
    }

    public function test_con_edicion_las_fechas_no_se_piden_ni_se_devuelven(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $datos = $this->datosGrupo($this->moduloParaGrupo($edicion), $edicion, ['fecha_inicio' => 'basura']);

        $this->validar($datos)->assertOk()->assertJsonMissingPath('fecha_inicio');
    }

    public function test_el_modulo_debe_pertenecer_al_programa_de_la_edicion(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-05-04', '2026-06-20');
        $ajeno = $this->moduloParaGrupo();

        $this->validar($this->datosGrupo($ajeno, $edicion))->assertJsonValidationErrors('modulo_id');
    }

    public function test_el_aula_debe_caber_el_cupo_minimo_salvo_en_virtual(): void
    {
        $aula = Aula::create(['nombre' => 'DEMO Chica', 'capacidad' => 10]);
        $modulo = $this->moduloParaGrupo();
        $mensaje = 'La capacidad del aula (10 personas) es inferior al cupo mínimo configurado (20).';

        $this->validar($this->datosGrupo($modulo, null, ['modalidad' => 'presencial', 'aula_id' => $aula->id, 'cupo_minimo' => 20]))
            ->assertJsonValidationErrors(['aula_id' => $mensaje]);
        $this->validar($this->datosGrupo($modulo, null, ['modalidad' => 'mixta', 'aula_id' => $aula->id, 'cupo_minimo' => 20]))
            ->assertJsonValidationErrors(['aula_id' => $mensaje]);
        $this->validar($this->datosGrupo($modulo, null, ['modalidad' => 'virtual', 'aula_id' => $aula->id, 'cupo_minimo' => 20]))
            ->assertOk();
    }

    public function test_solo_docentes_pueden_ser_titulares(): void
    {
        $this->validar($this->datosGrupo($this->moduloParaGrupo(), null, ['docente_id' => $this->admin()->id]))
            ->assertJsonValidationErrors('docente_id');
    }

    public function test_el_solape_se_calcula_con_el_fin_mas_tardio_y_solo_entre_filas_validas(): void
    {
        $modulo = $this->moduloParaGrupo();

        // 18-22 contiene a 19-20 y a 21-22: un solo mensaje sobre la primera fila posterior que choca.
        $this->validar($this->datosGrupo($modulo, null, ['horarios' => [
            $this->horario(1, '18:00', '22:00'), $this->horario(1, '19:00', '20:00'), $this->horario(1, '21:00', '22:00'),
        ]]))->assertJsonValidationErrors(['horarios.1.hora_inicio' => 'En lunes, los horarios se solapan.'])
            ->assertJsonMissingValidationErrors('horarios.2.hora_inicio');

        // La fila 1 es inválida (fin <= inicio) y no participa del cálculo de solapes.
        $this->validar($this->datosGrupo($modulo, null, ['horarios' => [
            $this->horario(2, '18:00', '20:00'), $this->horario(2, '19:00', '19:00'),
        ]]))->assertJsonValidationErrors('horarios.1.hora_fin')
            ->assertJsonMissingValidationErrors('horarios.1.hora_inicio');
    }

    public function test_un_dia_en_texto_o_una_fila_que_no_es_arreglo_no_rompen_la_validacion(): void
    {
        $this->validar($this->datosGrupo($this->moduloParaGrupo(), null, ['horarios' => ['x', ['dia_semana' => 'lunes', 'hora_inicio' => '10:00', 'hora_fin' => '11:00']]]))
            ->assertStatus(422);
    }
}
