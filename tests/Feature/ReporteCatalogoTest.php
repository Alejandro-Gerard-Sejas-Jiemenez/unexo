<?php

namespace Tests\Feature;

use App\Enums\EstadoComercial;
use App\Models\Programa;
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
        $respuesta = $this->actingAs($this->admin())->get(route('reportes.catalogo'));
        $respuesta->assertOk();

        return $respuesta->viewData('reporte');
    }

    private function conModulo(Programa $programa, EstadoComercial $estado): void
    {
        $programa->modulos()->attach($this->modulo($estado)->id, ['orden' => $programa->modulos()->count() + 1]);
    }

    // RF-13 / CA-13
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

    // RF-14, RF-29 / CA-14, CA-29
    public function test_la_oferta_visible_sigue_la_regla_de_rf29(): void
    {
        $conContenido = $this->programa(EstadoComercial::Abierto);
        $this->conModulo($conContenido, EstadoComercial::Abierto);
        $vacio = $this->programa(EstadoComercial::Abierto);
        $this->conModulo($vacio, EstadoComercial::Cerrado);
        $this->modulo(EstadoComercial::Abierto); // módulo suelto abierto
        $borrador = $this->programa(EstadoComercial::Borrador);
        $this->conModulo($borrador, EstadoComercial::Borrador);

        $respuesta = $this->actingAs($this->admin())->get(route('reportes.catalogo'));
        $reporte = $respuesta->viewData('reporte');

        $this->assertSame(1, $this->totalDe($reporte, 'Programas en la oferta visible'));
        $this->assertSame(2, $this->totalDe($reporte, 'Módulos en la oferta visible'));
        $this->assertSame('1', $this->numeroDeTarjeta($respuesta->getContent(), 'Programas en la oferta visible'));
        $this->assertSame('2', $this->numeroDeTarjeta($respuesta->getContent(), 'Módulos en la oferta visible'));
        $respuesta->assertDontSee('en la oferta visible para el CRM'); // RF-30: sin frases
    }

    // RF-15 / CA-15
    public function test_lista_los_programas_abiertos_sin_modulos_abiertos(): void
    {
        $vacio = $this->programa(EstadoComercial::Abierto, 'DEMO Vacío');
        $this->conModulo($vacio, EstadoComercial::Borrador);
        $conContenido = $this->programa(EstadoComercial::Abierto);
        $this->conModulo($conContenido, EstadoComercial::Abierto);
        $this->programa(EstadoComercial::Cerrado);

        $respuesta = $this->actingAs($this->admin())->get(route('reportes.catalogo'));

        $this->assertSame(
            [[$vacio->codigo, 'DEMO Vacío', 'El CRM lo ve vacío']],
            $this->filasDe($respuesta->viewData('reporte'), self::VACIOS),
        );
        $respuesta->assertSee('El CRM lo ve vacío');
    }

    public function test_sin_programas_vacios_muestra_el_mensaje(): void
    {
        $programa = $this->programa(EstadoComercial::Abierto);
        $this->conModulo($programa, EstadoComercial::Abierto);

        $respuesta = $this->actingAs($this->admin())->get(route('reportes.catalogo'));

        $this->assertSame([], $this->filasDe($respuesta->viewData('reporte'), self::VACIOS));
        $respuesta->assertSee('No hay programas abiertos sin módulos abiertos.');
    }

    public function test_los_programas_vacios_se_ordenan_por_nombre(): void
    {
        $this->programa(EstadoComercial::Abierto, 'DEMO B');
        $this->programa(EstadoComercial::Abierto, 'DEMO A');

        $this->assertSame(['DEMO A', 'DEMO B'], array_column($this->filasDe($this->reporte(), self::VACIOS), 1));
    }

    // RF-14: sin precios
    public function test_no_muestra_precios(): void
    {
        $programa = $this->programa(EstadoComercial::Abierto);
        $programa->update(['precio' => 2500]);

        $this->actingAs($this->admin())->get(route('reportes.catalogo'))
            ->assertOk()
            ->assertDontSee('2500')
            ->assertDontSee('2.500')
            ->assertDontSee('2,500');
    }

    // CA-40 (parte negativa): el reporte no muestra créditos ni nivel y no lista grupos
    public function test_no_muestra_creditos_ni_nivel_ni_lista_grupos(): void
    {
        $programa = $this->programa(EstadoComercial::Abierto, 'DEMO Programa catálogo');
        $this->conModulo($programa, EstadoComercial::Abierto);
        $this->grupo(['nombre' => 'DEMO Grupo oculto']);

        $respuesta = $this->actingAs($this->admin())->get(route('reportes.catalogo'))->assertOk();

        $respuesta->assertDontSee('Créditos')
            ->assertDontSee('Nivel')
            ->assertDontSee('DEMO Grupo oculto');
        $titulos = array_column($respuesta->viewData('reporte')->secciones, 'titulo');
        $this->assertNotContains('Grupos', $titulos);
    }

    // CA-37
    public function test_sin_datos_muestra_ceros_y_el_aviso(): void
    {
        $respuesta = $this->actingAs($this->admin())->get(route('reportes.catalogo'));
        $reporte = $respuesta->viewData('reporte');

        $this->assertTrue($reporte->sinDatos);
        $this->assertSame(0, $this->totalDe($reporte, 'Programas en la oferta visible'));
        $this->assertSame(0, $this->totalDe($reporte, 'Módulos en la oferta visible'));
        $this->assertSame([], $reporte->secciones);
        $respuesta->assertSee(Reporte::AVISO_SIN_DATOS);
    }

    public function test_el_catalogo_no_tiene_filtros(): void
    {
        $this->assertSame([['etiqueta' => 'Filtros', 'valor' => 'Sin filtros']], $this->reporte()->filtros);
    }
}
