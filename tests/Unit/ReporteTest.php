<?php

namespace Tests\Unit;

use App\Support\Reporte;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class ReporteTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function reporte(bool $sinDatos = false): Reporte
    {
        return new Reporte(
            titulo: 'Reporte de prueba',
            archivo: 'prueba',
            generadoEn: Reporte::ahora(),
            filtros: [['etiqueta' => 'Programa', 'valor' => 'Todos']],
            totales: [['etiqueta' => 'Total', 'valor' => 6, 'texto' => '6 cosas']],
            secciones: [],
            sinDatos: $sinDatos,
        );
    }

    // RF-04 / CA-04
    public function test_el_porcentaje_se_redondea_a_un_decimal(): void
    {
        $this->assertSame(33.3, Reporte::porcentaje(2, 6));
        $this->assertSame(50.0, Reporte::porcentaje(3, 6));
        $this->assertSame(16.7, Reporte::porcentaje(1, 6));
    }

    // CA-36: no se ajusta para que sume 100,0
    public function test_los_porcentajes_no_se_ajustan_para_sumar_cien(): void
    {
        $suma = Reporte::porcentaje(1, 3) * 3;

        $this->assertSame(33.3, Reporte::porcentaje(1, 3));
        $this->assertEqualsWithDelta(99.9, $suma, 0.0001);
    }

    // CA-37
    public function test_el_porcentaje_con_total_cero_es_cero(): void
    {
        $this->assertSame(0.0, Reporte::porcentaje(0, 0));
    }

    // RF-16 / CA-16
    public function test_formatea_porcentaje_fecha_entero_texto_y_nulo(): void
    {
        $this->assertSame('33,3 %', Reporte::formatear(33.3, 'porcentaje'));
        $this->assertSame('0,0 %', Reporte::formatear(0.0, 'porcentaje'));
        $this->assertSame('30/03/2026', Reporte::formatear(Carbon::parse('2026-03-30'), 'fecha'));
        $this->assertSame('1200', Reporte::formatear(1200, 'entero'));
        $this->assertSame('Hola', Reporte::formatear('Hola', 'texto'));
        $this->assertSame('—', Reporte::formatear(null, 'texto'));
        $this->assertSame('—', Reporte::formatear(null, 'entero'));
    }

    // RF-16 / CA-16: hora local de Bolivia (UTC-4)
    public function test_la_fecha_de_generacion_usa_la_hora_de_bolivia(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-30 16:05:00', 'UTC'));

        $this->assertSame('30/03/2026 12:05', $this->reporte()->fechaGeneracion());
    }

    public function test_el_nombre_del_archivo_lleva_la_hora_de_bolivia(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-30 16:05:00', 'UTC'));

        $this->assertSame('reporte-prueba-20260330-1205.pdf', $this->reporte()->nombreArchivo('pdf'));
    }
}
