<?php

namespace Tests\Feature;

use App\Enums\EstadoComercial;
use App\Models\Programa;
use App\Services\ReporteCatalogoService;
use App\Support\Reporte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaDatosDeReportes;
use Tests\TestCase;

class ReporteCatalogoTest extends TestCase
{
    use CreaDatosDeReportes;
    use RefreshDatabase;

    private const POR_ESTADO = 'Programas y módulos por estado comercial';

    private const VACIOS = 'Programas abiertos sin módulos abiertos (el CRM los ve vacíos)';

    private function reporte(): Reporte
    {
        $this->actingAs($this->admin())->getJson('/api/panel/reportes/catalogo')->assertOk();

        return app(ReporteCatalogoService::class)->generar();
    }

    private function conModulo(Programa $programa, EstadoComercial $estado): void
    {
        $programa->modulos()->attach($this->modulo($estado)->id, ['orden' => $programa->modulos()->count() + 1]);
    }

    public function test_cantidades_por_estado_comercial(): void
    {
        $this->programa(EstadoComercial::Borrador);
        $this->programa(EstadoComercial::Abierto);
        $this->programa(EstadoComercial::Abierto);
        $this->modulo(EstadoComercial::Cerrado);
        $this->modulo(EstadoComercial::Cerrado);
        $this->modulo(EstadoComercial::Cerrado);

        $this->assertSame([
            ['Borrador', 1, 0],
            ['Abierto', 2, 0],
            ['Cerrado', 0, 3],
        ], $this->filasDe($this->reporte(), self::POR_ESTADO));
    }

    public function test_la_oferta_visible_sigue_la_regla_de_rf29(): void
    {
        $conContenido = $this->programa(EstadoComercial::Abierto);
        $this->conModulo($conContenido, EstadoComercial::Abierto);
        $vacio = $this->programa(EstadoComercial::Abierto);
        $this->conModulo($vacio, EstadoComercial::Cerrado);
        $this->modulo(EstadoComercial::Abierto);
        $borrador = $this->programa(EstadoComercial::Borrador);
        $this->conModulo($borrador, EstadoComercial::Borrador);

        $res = $this->actingAs($this->admin())->getJson('/api/panel/reportes/catalogo')->assertOk();
        $reporte = $this->reporte();

        $this->assertSame(1, $this->totalDe($reporte, 'Programas en la oferta visible'));
        $this->assertSame(2, $this->totalDe($reporte, 'Módulos en la oferta visible'));
        $this->assertSame('1', (string) $res->json('totales.0.valor'));
        $this->assertSame('2', (string) $res->json('totales.1.valor'));
    }

    public function test_lista_los_programas_abiertos_sin_modulos_abiertos(): void
    {
        $vacio = $this->programa(EstadoComercial::Abierto, 'DEMO Vacío');
        $this->conModulo($vacio, EstadoComercial::Borrador);
        $conContenido = $this->programa(EstadoComercial::Abierto);
        $this->conModulo($conContenido, EstadoComercial::Abierto);
        $this->programa(EstadoComercial::Cerrado);

        $res = $this->actingAs($this->admin())->getJson('/api/panel/reportes/catalogo')->assertOk();
        $reporte = $this->reporte();

        $this->assertSame(
            [[$vacio->codigo, 'DEMO Vacío', 'El CRM lo ve vacío']],
            $this->filasDe($reporte, self::VACIOS),
        );
        $this->assertSame('El CRM lo ve vacío', $res->json('secciones.1.filas.0.2'));
    }

    public function test_sin_programas_vacios_muestra_el_mensaje(): void
    {
        $programa = $this->programa(EstadoComercial::Abierto);
        $this->conModulo($programa, EstadoComercial::Abierto);

        $res = $this->actingAs($this->admin())->getJson('/api/panel/reportes/catalogo')->assertOk();

        $this->assertSame([], $this->filasDe($this->reporte(), self::VACIOS));
        $this->assertSame('No hay programas abiertos sin módulos abiertos.', $res->json('secciones.1.vacio'));
    }

    public function test_los_programas_vacios_se_ordenan_por_nombre(): void
    {
        $this->programa(EstadoComercial::Abierto, 'DEMO B');
        $this->programa(EstadoComercial::Abierto, 'DEMO A');

        $this->assertSame(['DEMO A', 'DEMO B'], array_column($this->filasDe($this->reporte(), self::VACIOS), 1));
    }

    public function test_no_muestra_precios(): void
    {
        $programa = $this->programa(EstadoComercial::Abierto);
        $programa->update(['precio' => 2500]);

        $contenido = $this->actingAs($this->admin())->getJson('/api/panel/reportes/catalogo')->assertOk()->getContent();
        $this->assertStringNotContainsString('2500', $contenido);
        $this->assertStringNotContainsString('2.500', $contenido);
        $this->assertStringNotContainsString('2,500', $contenido);
    }

    public function test_no_muestra_creditos_ni_nivel_ni_lista_grupos(): void
    {
        $programa = $this->programa(EstadoComercial::Abierto, 'DEMO Programa catálogo');
        $this->conModulo($programa, EstadoComercial::Abierto);
        $this->grupo(['nombre' => 'DEMO Grupo oculto']);

        $res = $this->actingAs($this->admin())->getJson('/api/panel/reportes/catalogo')->assertOk();
        $contenido = $res->getContent();

        $this->assertStringNotContainsString('Créditos', $contenido);
        $this->assertStringNotContainsString('Nivel', $contenido);
        $this->assertStringNotContainsString('DEMO Grupo oculto', $contenido);
        $this->assertNotContains('Grupos', array_column($res->json('secciones'), 'titulo'));
    }

    public function test_sin_datos_muestra_ceros_y_el_aviso(): void
    {
        $res = $this->actingAs($this->admin())->getJson('/api/panel/reportes/catalogo')->assertOk();
        $reporte = $this->reporte();

        $this->assertTrue($reporte->sinDatos);
        $this->assertSame(0, $this->totalDe($reporte, 'Programas en la oferta visible'));
        $this->assertSame(0, $this->totalDe($reporte, 'Módulos en la oferta visible'));
        $this->assertSame([], $reporte->secciones);
        $this->assertSame(Reporte::AVISO_SIN_DATOS, $res->json('aviso_sin_datos'));
    }

    public function test_el_catalogo_no_tiene_filtros(): void
    {
        $this->assertSame([['etiqueta' => 'Filtros', 'valor' => 'Sin filtros']], $this->reporte()->filtros);
    }
}
