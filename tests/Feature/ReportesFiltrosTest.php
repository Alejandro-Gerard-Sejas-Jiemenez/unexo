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

    private function pantalla(string $ruta, array $filtros)
    {
        return $this->actingAs($this->admin)->get(route($ruta, $filtros));
    }

    // RF-19 / CA-19 (matrículas y ocupación)
    public function test_fecha_final_anterior_muestra_el_error_y_no_calcula(): void
    {
        $this->matricula();
        $this->grupo();

        foreach (['reportes.matriculas', 'reportes.ocupacion'] as $ruta) {
            $respuesta = $this->pantalla($ruta, ['desde' => '2026-05-01', 'hasta' => '2026-01-01']);

            $respuesta->assertStatus(422)
                ->assertSee(self::RANGO)
                ->assertSee('2026-05-01', false)
                ->assertDontSee('Generado el');
            $this->assertNull($respuesta->viewData('reporte'));
        }
    }

    // RF-19 / CA-35
    public function test_una_fecha_mal_formada_muestra_un_unico_error(): void
    {
        foreach (['reportes.matriculas', 'reportes.ocupacion'] as $ruta) {
            foreach (['mañana', '2026-02-30', '30/03/2026', '2026-3-5'] as $fecha) {
                $respuesta = $this->pantalla($ruta, ['desde' => $fecha, 'hasta' => '2026-01-01']);

                $respuesta->assertStatus(422)->assertSee(self::FECHA)->assertDontSee(self::RANGO);
                $this->assertSame(1, $respuesta->viewData('errors')->count());
            }
        }
    }

    public function test_una_fecha_enviada_como_arreglo_es_invalida(): void
    {
        $this->pantalla('reportes.matriculas', ['desde' => ['2026-01-01']])
            ->assertStatus(422)
            ->assertSee(self::FECHA);
    }

    // RF-19 / CA-34
    public function test_un_programa_o_cohorte_inexistente_es_un_filtro_invalido(): void
    {
        $this->matricula();
        $inexistente = '00000000-0000-4000-8000-000000000000';

        foreach ([['programa_id', 'reportes.matriculas'], ['cohorte_id', 'reportes.matriculas'], ['cohorte_id', 'reportes.ocupacion']] as [$campo, $ruta]) {
            foreach ([$inexistente, 'abc', ['x']] as $valor) {
                $respuesta = $this->pantalla($ruta, [$campo => $valor]);

                $respuesta->assertStatus(422)->assertSee(self::INEXISTENTE);
                $this->assertNull($respuesta->viewData('reporte'));
            }
        }
    }

    // P-4
    public function test_un_estado_de_grupo_fuera_del_enum_es_invalido(): void
    {
        $this->pantalla('reportes.ocupacion', ['estado' => 'inventado'])
            ->assertStatus(422)
            ->assertSee('no es válido');
    }

    public function test_los_filtros_vacios_equivalen_a_no_aplicados(): void
    {
        $this->matricula();

        $this->pantalla('reportes.matriculas', ['desde' => '', 'hasta' => '', 'programa_id' => '', 'cohorte_id' => ''])
            ->assertOk();
    }

    public function test_el_catalogo_ignora_cualquier_parametro(): void
    {
        $this->pantalla('reportes.catalogo', ['desde' => 'basura', 'programa_id' => 'abc'])->assertOk();
    }

    // El catálogo no tiene filtros: un rango invertido en la URL tampoco debe romperlo (antes devolvía 500)
    public function test_el_catalogo_ignora_un_rango_de_fechas_invertido(): void
    {
        $rango = ['desde' => '2026-05-01', 'hasta' => '2026-01-01'];

        $this->pantalla('reportes.catalogo', $rango)->assertOk()->assertDontSee(self::RANGO);
        $this->actingAs($this->admin)->get(route('reportes.catalogo.pdf', $rango))->assertOk();
        $this->actingAs($this->admin)->get(route('reportes.catalogo.excel', $rango))->assertOk();
    }

    // RF-19 aplicado a las descargas (cobertura adicional, HU-01..06)
    public function test_las_descargas_con_filtro_invalido_redirigen_a_la_pantalla_con_la_misma_consulta(): void
    {
        $consulta = ['desde' => '2026-05-01', 'hasta' => '2026-01-01'];

        foreach ([
            ['reportes.matriculas.pdf', 'reportes.matriculas'],
            ['reportes.matriculas.excel', 'reportes.matriculas'],
            ['reportes.ocupacion.pdf', 'reportes.ocupacion'],
            ['reportes.ocupacion.excel', 'reportes.ocupacion'],
        ] as [$descarga, $pantalla]) {
            $this->actingAs($this->admin)
                ->get(route($descarga, $consulta))
                ->assertRedirect(route($pantalla, $consulta));
        }
    }

    public function test_la_pantalla_conserva_los_valores_ingresados_al_fallar(): void
    {
        $this->pantalla('reportes.ocupacion', ['desde' => '2026-05-01', 'hasta' => '2026-01-01'])
            ->assertStatus(422)
            ->assertSee('value="2026-05-01"', false)
            ->assertSee('value="2026-01-01"', false);
    }
}
