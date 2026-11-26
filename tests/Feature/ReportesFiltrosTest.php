<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaDatosDeReportes;
use Tests\TestCase;

class ReportesFiltrosTest extends TestCase
{
    use CreaDatosDeReportes;
    use RefreshDatabase;

    private const RANGO = 'La fecha final no puede ser anterior a la fecha inicial.';

    private const FECHA = 'La fecha ingresada no es válida.';

    private const INEXISTENTE = 'El programa o la cohorte elegidos no existen.';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->admin();
    }

    private function consultar(string $ruta, array $filtros = [])
    {
        return $this->actingAs($this->admin)->getJson($ruta.'?'.http_build_query($filtros));
    }

    public function test_fecha_final_anterior_muestra_el_error_y_no_calcula(): void
    {
        $this->matricula();
        $this->grupo();

        foreach (['/api/panel/reportes/matriculas', '/api/panel/reportes/ocupacion'] as $ruta) {
            $respuesta = $this->consultar($ruta, ['desde' => '2026-05-01', 'hasta' => '2026-01-01']);

            $respuesta->assertStatus(422)
                ->assertJsonPath('errors.hasta.0', self::RANGO);
        }
    }

    public function test_una_fecha_mal_formada_muestra_un_unico_error(): void
    {
        foreach (['/api/panel/reportes/matriculas', '/api/panel/reportes/ocupacion'] as $ruta) {
            foreach (['mañana', '2026-02-30', '30/03/2026', '2026-3-5'] as $fecha) {
                $respuesta = $this->consultar($ruta, ['desde' => $fecha, 'hasta' => '2026-01-01']);

                $respuesta->assertStatus(422)
                    ->assertJsonPath('errors.desde.0', self::FECHA);
                $this->assertCount(1, $respuesta->json('errors'));
            }
        }
    }

    public function test_una_fecha_enviada_como_arreglo_es_invalida(): void
    {
        $this->consultar('/api/panel/reportes/matriculas', ['desde' => ['2026-01-01']])
            ->assertStatus(422)
            ->assertJsonPath('errors.desde.0', self::FECHA);
    }

    public function test_un_programa_o_cohorte_inexistente_es_un_filtro_invalido(): void
    {
        $this->matricula();
        $inexistente = '00000000-0000-4000-8000-000000000000';

        foreach ([
            ['programa_id', '/api/panel/reportes/matriculas'],
            ['cohorte_id', '/api/panel/reportes/matriculas'],
            ['cohorte_id', '/api/panel/reportes/ocupacion'],
        ] as [$campo, $ruta]) {
            foreach ([$inexistente, 'abc', ['x']] as $valor) {
                $this->consultar($ruta, [$campo => $valor])
                    ->assertStatus(422)
                    ->assertJsonPath("errors.{$campo}.0", self::INEXISTENTE);
            }
        }
    }

    public function test_un_estado_de_grupo_fuera_del_enum_es_invalido(): void
    {
        $this->consultar('/api/panel/reportes/ocupacion', ['estado' => 'inventado'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('estado');
    }

    public function test_los_filtros_vacios_equivalen_a_no_aplicados(): void
    {
        $this->matricula();

        $this->consultar('/api/panel/reportes/matriculas', ['desde' => '', 'hasta' => '', 'programa_id' => '', 'cohorte_id' => ''])
            ->assertOk();
    }

    public function test_el_catalogo_ignora_cualquier_parametro(): void
    {
        $this->consultar('/api/panel/reportes/catalogo', ['desde' => 'basura', 'programa_id' => 'abc'])->assertOk();
    }

    public function test_el_catalogo_ignora_un_rango_de_fechas_invertido(): void
    {
        $rango = ['desde' => '2026-05-01', 'hasta' => '2026-01-01'];

        $this->consultar('/api/panel/reportes/catalogo', $rango)->assertOk();
        $this->consultar('/api/panel/reportes/catalogo/pdf', $rango)->assertOk();
        $this->consultar('/api/panel/reportes/catalogo/excel', $rango)->assertOk();
    }

    public function test_las_descargas_con_filtro_invalido_responden_422_en_json(): void
    {
        $consulta = ['desde' => '2026-05-01', 'hasta' => '2026-01-01'];

        foreach ([
            '/api/panel/reportes/matriculas/pdf',
            '/api/panel/reportes/matriculas/excel',
            '/api/panel/reportes/ocupacion/pdf',
            '/api/panel/reportes/ocupacion/excel',
        ] as $descarga) {
            $this->consultar($descarga, $consulta)
                ->assertStatus(422)
                ->assertJsonPath('errors.hasta.0', self::RANGO);
        }
    }
}
