<?php

namespace Tests\Feature;

use Tests\TestCase;

/** La tabla y los filtros de los reportes salen de componentes compartidos, no de marcado repetido en cada vista. */
class ComponentesDeReportesTest extends TestCase
{
    private function columnas(): array
    {
        return [
            ['titulo' => 'Estado', 'tipo' => 'texto'],
            ['titulo' => 'Cantidad', 'tipo' => 'entero'],
            ['titulo' => 'Porcentaje', 'tipo' => 'porcentaje'],
        ];
    }

    public function test_la_tabla_dibuja_titulo_encabezados_y_celdas_formateadas(): void
    {
        $vista = $this->blade('<x-tabla :titulo="$titulo" :columnas="$columnas" :filas="$filas" />', [
            'titulo' => 'Matrículas por estado',
            'columnas' => $this->columnas(),
            'filas' => [['Activo (pagado)', 3, 50.0], ['Retirado', 1, null]],
        ]);

        $vista->assertSee('<caption', false)
            ->assertSee('Matrículas por estado')
            ->assertSee('scope="col"', false)
            ->assertSeeInOrder(['Estado', 'Cantidad', 'Porcentaje', 'Activo (pagado)', '3', '50,0 %', 'Retirado', '1', '—']);
    }

    public function test_la_tabla_alinea_a_la_derecha_las_columnas_numericas(): void
    {
        $html = (string) $this->blade('<x-tabla titulo="T" :columnas="$columnas" :filas="$filas" />', [
            'columnas' => $this->columnas(),
            'filas' => [['A', 1, 10.0]],
        ]);

        $this->assertSame(4, substr_count($html, 'text-right')); // 2 encabezados + 2 celdas
    }

    public function test_la_tabla_sin_filas_muestra_el_mensaje_indicado_o_el_aviso_general(): void
    {
        $this->blade('<x-tabla titulo="T" :columnas="$columnas" :filas="[]" vacio="No hay nada." />', ['columnas' => $this->columnas()])
            ->assertSee('No hay nada.');
        $this->blade('<x-tabla titulo="T" :columnas="$columnas" :filas="[]" />', ['columnas' => $this->columnas()])
            ->assertSee('Sin datos para los filtros aplicados');
    }

    public function test_los_filtros_traen_formulario_botones_y_el_contenido_de_los_campos(): void
    {
        $this->blade('<x-filtros accion="/reportes/x" quitar="/reportes/x"><input name="desde" id="desde"></x-filtros>')
            ->assertSee('method="GET"', false)
            ->assertSee('action="/reportes/x"', false)
            ->assertSee('name="desde"', false)
            ->assertSee('Aplicar filtros')
            ->assertSee('Quitar filtros');
    }

    public function test_las_vistas_de_reportes_no_repiten_tablas_ni_formularios_de_filtros(): void
    {
        foreach (['_cuerpo', 'matriculas', 'ocupacion', 'catalogo'] as $vista) {
            $contenido = file_get_contents(resource_path("views/reportes/$vista.blade.php"));

            $this->assertStringNotContainsString('<table', $contenido, "reportes/$vista repite una tabla en lugar de usar <x-tabla>.");
            $this->assertStringNotContainsString('<form', $contenido, "reportes/$vista repite un formulario en lugar de usar <x-filtros>.");
        }
    }
}
