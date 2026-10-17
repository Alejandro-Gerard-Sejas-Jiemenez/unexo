<?php

namespace Tests\Feature;

use App\Enums\EstadoMatricula;
use App\Enums\OrigenMatricula;
use App\Support\Reporte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaDatosDeReportes;
use Tests\TestCase;

class ReporteMatriculasTest extends TestCase
{
    use CreaDatosDeReportes;
    use RefreshDatabase;

    private function reporte(array $filtros = []): Reporte
    {
        $respuesta = $this->actingAs($this->admin())->get(route('reportes.matriculas', $filtros));
        $respuesta->assertOk();

        return $respuesta->viewData('reporte');
    }

    private function crear(int $cantidad, array $atributos = []): void
    {
        for ($i = 0; $i < $cantidad; $i++) {
            $this->matricula($atributos);
        }
    }

    // RF-04 / CA-04
    public function test_totales_y_porcentajes_por_estado(): void
    {
        $this->crear(2, ['estado' => EstadoMatricula::Pendiente]);
        $this->crear(3, ['estado' => EstadoMatricula::Activo]);
        $this->crear(1, ['estado' => EstadoMatricula::Retirado]);

        $respuesta = $this->actingAs($this->admin())->get(route('reportes.matriculas'));
        $reporte = $respuesta->viewData('reporte');

        $this->assertSame(6, $this->totalDe($reporte, 'Total de matrículas'));
        $this->assertSame([
            ['Pendiente de pago', 2, 33.3],
            ['Activo (pagado)', 3, 50.0],
            ['Retirado', 1, 16.7],
        ], $this->filasDe($reporte, 'Matrículas por estado'));
        $respuesta->assertSee('33,3 %')->assertSee('50,0 %')->assertSee('16,7 %');
    }

    // RF-30 / CA-39
    public function test_el_total_es_una_tarjeta_con_solo_el_numero(): void
    {
        $this->crear(6);

        $html = $this->actingAs($this->admin())->get(route('reportes.matriculas'))->getContent();

        $this->assertSame('6', $this->numeroDeTarjeta($html, 'Total de matrículas'));
        $this->assertStringNotContainsString('6 matrículas', $html);
    }

    // CA-36
    public function test_los_porcentajes_no_se_ajustan_para_sumar_cien(): void
    {
        $this->matricula(['estado' => EstadoMatricula::Pendiente]);
        $this->matricula(['estado' => EstadoMatricula::Activo]);
        $this->matricula(['estado' => EstadoMatricula::Retirado]);

        $porcentajes = array_column($this->filasDe($this->reporte(), 'Matrículas por estado'), 2);

        $this->assertSame([33.3, 33.3, 33.3], $porcentajes);
    }

    // RF-05 / CA-05
    public function test_desglose_por_origen_con_cantidad_y_porcentaje(): void
    {
        $this->crear(3, ['origen' => OrigenMatricula::Crm]);
        $this->crear(1, ['origen' => OrigenMatricula::Manual]);

        $this->assertSame([
            ['Desde el CRM', 3, 75.0],
            ['Carga manual', 1, 25.0],
        ], $this->filasDe($this->reporte(), 'Matrículas por origen'));
    }

    // RF-06 / CA-06
    public function test_el_rango_incluye_ambos_extremos(): void
    {
        foreach (['2026-03-01', '2026-03-15', '2026-03-30', '2026-04-01'] as $fecha) {
            $this->matricula(['fecha_matricula' => $fecha]);
        }

        $reporte = $this->reporte(['desde' => '2026-03-15', 'hasta' => '2026-03-30']);

        $this->assertSame(2, $this->totalDe($reporte, 'Total de matrículas'));
    }

    // RF-06: se puede indicar un solo extremo
    public function test_se_puede_filtrar_con_un_solo_extremo(): void
    {
        foreach (['2026-03-01', '2026-03-15', '2026-04-01'] as $fecha) {
            $this->matricula(['fecha_matricula' => $fecha]);
        }

        $this->assertSame(2, $this->totalDe($this->reporte(['desde' => '2026-03-15']), 'Total de matrículas'));
        $this->assertSame(2, $this->totalDe($this->reporte(['hasta' => '2026-03-15']), 'Total de matrículas'));
    }

    // RF-06 / CA-30
    public function test_filtra_por_programa_y_excluye_los_modulos_sueltos(): void
    {
        $programaA = $this->programa();
        $programaB = $this->programa();
        $this->crear(2, ['cohorte' => $this->cohorte($programaA)]);
        $this->crear(1, ['cohorte' => $this->cohorte($programaB)]);
        $this->crear(4); // módulos sueltos

        $reporte = $this->reporte(['programa_id' => $programaA->id]);

        $this->assertSame(2, $this->totalDe($reporte, 'Total de matrículas'));
    }

    // RF-06 / CA-31
    public function test_cohorte_y_rango_se_combinan_a_la_vez(): void
    {
        $cohorteA = $this->cohorte();
        $cohorteB = $this->cohorte();
        $this->matricula(['cohorte' => $cohorteA, 'fecha_matricula' => '2026-03-10']);
        $this->matricula(['cohorte' => $cohorteA, 'fecha_matricula' => '2026-05-10']);
        $this->matricula(['cohorte' => $cohorteB, 'fecha_matricula' => '2026-03-10']);

        $reporte = $this->reporte(['cohorte_id' => $cohorteA->id, 'desde' => '2026-03-01', 'hasta' => '2026-03-31']);

        $this->assertSame(1, $this->totalDe($reporte, 'Total de matrículas'));
    }

    // RF-07 / CA-07
    public function test_la_matricula_sin_cohorte_se_cuenta_bajo_sin_cohorte(): void
    {
        $programa = $this->programa(nombre: 'DEMO Diplomado');
        $cohorte = $this->cohorte($programa, 'DEMO Cohorte A');
        $this->matricula(['cohorte' => $cohorte]);
        $this->matricula();

        $filas = $this->filasDe($this->reporte(), 'Matrículas por cohorte');

        $this->assertSame([
            ['DEMO Diplomado', 'DEMO Cohorte A', 1, 50.0],
            [null, 'Sin cohorte', 1, 50.0],
        ], $filas);
    }

    public function test_las_cohortes_se_ordenan_por_programa_y_cohorte_y_sin_cohorte_va_al_final(): void
    {
        $programaA = $this->programa(nombre: 'DEMO A');
        $this->matricula();
        $this->matricula(['cohorte' => $this->cohorte($this->programa(nombre: 'DEMO B'), 'DEMO Z')]);
        $this->matricula(['cohorte' => $this->cohorte($programaA, 'DEMO Y')]);
        $this->matricula(['cohorte' => $this->cohorte($programaA, 'DEMO X')]);

        $filas = $this->filasDe($this->reporte(), 'Matrículas por cohorte');

        $this->assertSame(
            ['DEMO X', 'DEMO Y', 'DEMO Z', 'Sin cohorte'],
            array_column($filas, 1),
        );
    }

    // CA-37
    public function test_sin_matriculas_muestra_ceros_y_el_aviso(): void
    {
        $respuesta = $this->actingAs($this->admin())->get(route('reportes.matriculas'));
        $reporte = $respuesta->viewData('reporte');

        $this->assertTrue($reporte->sinDatos);
        $this->assertSame(0, $this->totalDe($reporte, 'Total de matrículas'));
        $this->assertSame([], $reporte->secciones);
        $respuesta->assertSee(Reporte::AVISO_SIN_DATOS);
    }

    // RF-17: filtros sin resultados
    public function test_un_filtro_sin_resultados_muestra_el_aviso(): void
    {
        $this->matricula(['fecha_matricula' => '2026-03-10']);

        $respuesta = $this->actingAs($this->admin())->get(route('reportes.matriculas', ['desde' => '2027-01-01']));

        $this->assertTrue($respuesta->viewData('reporte')->sinDatos);
        $respuesta->assertSee(Reporte::AVISO_SIN_DATOS);
    }

    // RNF-05
    public function test_las_etiquetas_de_estado_y_origen_son_las_del_sistema(): void
    {
        $this->matricula();
        $respuesta = $this->actingAs($this->admin())->get(route('reportes.matriculas'));

        foreach (EstadoMatricula::cases() as $estado) {
            $respuesta->assertSee($estado->label());
        }
        foreach (OrigenMatricula::cases() as $origen) {
            $respuesta->assertSee($origen->label());
        }
    }

    public function test_los_filtros_aplicados_se_listan_con_todos_en_los_no_aplicados(): void
    {
        $programa = $this->programa(nombre: 'DEMO Diplomado');

        $filtros = $this->reporte(['programa_id' => $programa->id, 'desde' => '2026-03-01'])->filtros;

        $this->assertSame([
            ['etiqueta' => 'Fecha de matrícula desde', 'valor' => '01/03/2026'],
            ['etiqueta' => 'Fecha de matrícula hasta', 'valor' => 'Todos'],
            ['etiqueta' => 'Programa', 'valor' => 'DEMO Diplomado'],
            ['etiqueta' => 'Cohorte', 'valor' => 'Todos'],
        ], $filtros);
    }
}
