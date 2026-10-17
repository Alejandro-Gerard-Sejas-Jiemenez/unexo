<?php

namespace Tests\Feature;

use App\Enums\EstadoComercial;
use App\Enums\EstadoMatricula;
use App\Enums\EstadoPlanificacion;
use App\Enums\OrigenMatricula;
use App\Services\ReporteExcelService;
use App\Services\ReportePdfService;
use App\Support\Reporte;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\XLSX\Reader;
use RuntimeException;
use Tests\Concerns\CreaDatosDeReportes;
use Tests\TestCase;

class ReportesDescargaTest extends TestCase
{
    use CreaDatosDeReportes;
    use RefreshDatabase;

    private const MENSAJE_FALLO = 'No se pudo generar el archivo. Intenta de nuevo.';

    private const XLSX = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function datosDeEjemplo(): void
    {
        $cohorte = $this->cohorte(nombre: 'DEMO Cohorte A');
        $this->matricula(['cohorte' => $cohorte, 'estado' => EstadoMatricula::Pendiente, 'origen' => OrigenMatricula::Crm]);
        $this->matricula(['cohorte' => $cohorte, 'estado' => EstadoMatricula::Activo]);
        $this->matricula(['estado' => EstadoMatricula::Retirado]);

        $grupo = $this->grupo(['nombre' => 'DEMO Grupo 1', 'cohorte' => $cohorte, 'cupo_minimo' => 5]);
        $this->inscribir($this->matricula(['cohorte' => $cohorte]), $grupo);
        $this->grupo(['nombre' => 'DEMO Cancelado', 'cupo_minimo' => 3, 'estado' => EstadoPlanificacion::Cancelado]);

        $programa = $this->programa(EstadoComercial::Abierto, 'DEMO Vacío');
        $conContenido = $this->programa(EstadoComercial::Abierto);
        $conContenido->modulos()->attach($this->modulo(EstadoComercial::Abierto)->id, ['orden' => 1]);
    }

    /** @return array<int, array<int, mixed>> filas del primer libro, sin las filas en blanco */
    private function leerExcel($respuesta): array
    {
        $ruta = $respuesta->baseResponse->getFile()->getPathname();
        $lector = new Reader;
        $lector->open($ruta);
        $filas = [];
        foreach ($lector->getSheetIterator() as $hoja) {
            $this->assertSame('Reporte', $hoja->getName());
            foreach ($hoja->getRowIterator() as $fila) {
                $filas[] = $fila->toArray();
            }
            break;
        }
        $lector->close();

        return $filas;
    }

    private function filaQueEmpiezaCon(array $filas, string $texto): array
    {
        foreach ($filas as $fila) {
            if (($fila[0] ?? null) === $texto) {
                return $fila;
            }
        }
        $this->fail("El Excel no tiene una fila que empiece con «{$texto}».");
    }

    private function desde(string $ruta, array $filtros = [])
    {
        return $this->actingAs($this->admin())->get(route($ruta, $filtros));
    }

    // RF-16 / CA-16
    public function test_el_pdf_responde_con_cabeceras_y_firma_pdf(): void
    {
        $this->datosDeEjemplo();

        foreach (['matriculas' => 'matriculas', 'ocupacion' => 'ocupacion-grupos', 'catalogo' => 'catalogo-oferta'] as $reporte => $archivo) {
            $respuesta = $this->desde("reportes.$reporte.pdf")->assertOk();

            $this->assertSame('application/pdf', $respuesta->headers->get('Content-Type'));
            $this->assertMatchesRegularExpression('/^attachment; filename="?reporte-'.$archivo.'-\d{8}-\d{4}\.pdf"?$/', $respuesta->headers->get('Content-Disposition'));
            $this->assertStringStartsWith('%PDF', $respuesta->getContent());
        }
    }

    // RF-16 / CA-16: la vista del PDF trae lo mismo que la pantalla, en el mismo orden, en los tres reportes
    public function test_la_vista_pdf_trae_titulo_fecha_de_la_paz_filtros_totales_y_el_orden_de_pantalla(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-30 16:05:00', 'UTC'));
        $this->datosDeEjemplo();

        foreach (['matriculas', 'ocupacion', 'catalogo'] as $nombre) {
            $reporte = $this->desde("reportes.$nombre")->viewData('reporte');
            $html = view('reportes.pdf', ['reporte' => $reporte])->render();

            $this->assertStringContainsString($reporte->titulo, $html);
            $this->assertStringContainsString('Generado el 30/03/2026 12:05', $html);
            $this->assertStringNotContainsString('<script', $html);
            $this->assertStringNotContainsString('http', $html);
            foreach ($reporte->filtros as $filtro) {
                $this->assertStringContainsString(e($filtro['etiqueta']), $html);
                $this->assertStringContainsString(e($filtro['valor']), $html);
            }
            foreach ($reporte->totales as $total) {
                $this->assertStringContainsString(e($total['etiqueta']), $html);
                $this->assertStringContainsString('>'.$total['valor'].'</td>', $html); // RF-30 / CA-39: nombre y número
            }

            // Cada celda aparece, en orden, dentro de su propia etiqueta <td> (evita coincidencias con "1" o "0" sueltos).
            $posicion = 0;
            foreach ($reporte->secciones as $seccion) {
                $this->assertNotFalse($posicion = strpos($html, e($seccion['titulo']), $posicion), "Falta la sección «{$seccion['titulo']}» en el PDF de $nombre.");
                foreach ($seccion['filas'] as $fila) {
                    foreach ($seccion['columnas'] as $i => $columna) {
                        $celda = '>'.e(Reporte::formatear($fila[$i], $columna['tipo'])).'</td>';
                        $this->assertNotFalse($posicion = strpos($html, $celda, $posicion), "«{$celda}» no aparece en el orden de la pantalla en el PDF de $nombre.");
                    }
                }
            }
        }
    }

    // RNF-05: las etiquetas son las mismas en pantalla, PDF y Excel
    public function test_las_etiquetas_de_estado_y_origen_son_las_mismas_en_pdf_y_excel(): void
    {
        $this->datosDeEjemplo();
        $reporte = $this->desde('reportes.matriculas')->viewData('reporte');
        $html = view('reportes.pdf', ['reporte' => $reporte])->render();
        $filas = $this->leerExcel($this->desde('reportes.matriculas.excel')->assertOk());
        $primeraColumna = array_column($filas, 0);

        foreach ([...EstadoMatricula::cases(), ...OrigenMatricula::cases()] as $caso) {
            $this->assertStringContainsString(e($caso->label()), $html);
            $this->assertContains($caso->label(), $primeraColumna);
        }
    }

    // RF-17 / CA-17
    public function test_un_filtro_sin_resultados_muestra_el_aviso_en_pantalla_y_en_pdf_y_en_excel(): void
    {
        $this->datosDeEjemplo();
        $consulta = ['desde' => '2030-01-01'];

        $pantalla = $this->desde('reportes.matriculas', $consulta)->assertOk()->assertSee(Reporte::AVISO_SIN_DATOS);
        $html = view('reportes.pdf', ['reporte' => $pantalla->viewData('reporte')])->render();
        $this->assertStringContainsString(Reporte::AVISO_SIN_DATOS, $html);

        $filas = $this->leerExcel($this->desde('reportes.matriculas.excel', $consulta)->assertOk());
        $this->filaQueEmpiezaCon($filas, Reporte::AVISO_SIN_DATOS);
    }

    // RF-23 / CA-23
    public function test_el_excel_trae_titulo_fecha_filtros_totales_y_tablas_en_el_orden_de_pantalla(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-30 16:05:00', 'UTC'));
        $this->datosDeEjemplo();

        $respuesta = $this->desde('reportes.matriculas.excel', ['desde' => '2026-03-01'])->assertOk();
        $filas = $this->leerExcel($respuesta);

        $this->assertSame(self::XLSX, $respuesta->headers->get('Content-Type'));
        $this->assertMatchesRegularExpression('/filename="?reporte-matriculas-20260330-1205\.xlsx"?/', $respuesta->headers->get('Content-Disposition'));
        $this->assertSame('Reporte de matrículas', $filas[0][0]);
        $this->assertSame('2026-03-30 12:05', $this->filaQueEmpiezaCon($filas, 'Generado el')[1]->format('Y-m-d H:i'));
        $this->assertSame('01/03/2026', $this->filaQueEmpiezaCon($filas, 'Fecha de matrícula desde')[1]);
        $this->assertSame('Todos', $this->filaQueEmpiezaCon($filas, 'Fecha de matrícula hasta')[1]);
        $this->assertSame(4, $this->filaQueEmpiezaCon($filas, 'Total de matrículas')[1]);

        $etiquetas = array_column($filas, 0);
        $this->assertSame(
            ['Matrículas por estado', 'Matrículas por origen', 'Matrículas por cohorte'],
            array_values(array_intersect($etiquetas, ['Matrículas por estado', 'Matrículas por origen', 'Matrículas por cohorte'])),
        );
        $this->assertSame(['Estado', 'Cantidad', 'Porcentaje (%)'], $this->filaQueEmpiezaCon($filas, 'Estado'));
        $this->assertSame(['Pendiente de pago', 1, 25], array_slice($this->filaQueEmpiezaCon($filas, 'Pendiente de pago'), 0, 3));
    }

    // RF-24 / CA-24
    public function test_el_excel_guarda_cantidades_y_porcentajes_como_numeros_y_fechas_como_fechas(): void
    {
        $this->datosDeEjemplo();

        $porcentajes = $this->leerExcel($this->desde('reportes.matriculas.excel')->assertOk());
        $fila = $this->filaQueEmpiezaCon($porcentajes, 'Activo (pagado)');
        $this->assertIsNumeric($fila[1]);
        $this->assertIsNumeric($fila[2]);
        $this->assertEquals(50, $fila[2]);

        $ocupacion = $this->leerExcel($this->desde('reportes.ocupacion.excel')->assertOk());
        $grupo = $this->filaQueEmpiezaCon($ocupacion, 'DEMO Grupo 1');
        $this->assertInstanceOf(DateTimeInterface::class, $grupo[4]);
        $this->assertInstanceOf(DateTimeInterface::class, $grupo[5]);
        $this->assertEquals(1, $grupo[7]);
        $this->assertEquals(5, $grupo[8]);
        $this->assertSame('No alcanza el mínimo', $grupo[9]);
        $this->assertContains($this->filaQueEmpiezaCon($ocupacion, 'DEMO Cancelado')[9] ?? null, [null, ''], 'El cancelado debe quedar sin indicador.');
    }

    public function test_el_excel_de_catalogo_trae_los_totales_y_las_dos_tablas(): void
    {
        $this->datosDeEjemplo();

        $filas = $this->leerExcel($this->desde('reportes.catalogo.excel')->assertOk());

        $this->assertSame('Reporte de catálogo y oferta', $filas[0][0]);
        $this->assertSame('Sin filtros', $this->filaQueEmpiezaCon($filas, 'Filtros')[1]);
        $this->assertSame(1, $this->filaQueEmpiezaCon($filas, 'Programas en la oferta visible')[1]);
        $this->assertSame(['Borrador', 0, 0], array_slice($this->filaQueEmpiezaCon($filas, 'Borrador'), 0, 3));
        $codigoVacio = $this->primerCodigoVacio($filas);
        $this->assertSame('El CRM lo ve vacío', $this->filaQueEmpiezaCon($filas, $codigoVacio)[2]);
    }

    private function primerCodigoVacio(array $filas): string
    {
        foreach ($filas as $i => $fila) {
            if (($fila[0] ?? null) === 'Código') {
                return $filas[$i + 1][0];
            }
        }
        $this->fail('No hay tabla de programas vacíos.');
    }

    // CA-38
    public function test_si_falla_el_pdf_se_vuelve_al_reporte_con_el_mensaje(): void
    {
        $this->mock(ReportePdfService::class)->shouldReceive('generar')->andThrow(new RuntimeException('falla simulada'));

        $consulta = ['desde' => '2026-03-01'];
        $this->desde('reportes.matriculas.pdf', $consulta)
            ->assertRedirect(route('reportes.matriculas', $consulta))
            ->assertSessionHasErrors(['descarga' => self::MENSAJE_FALLO]);
    }

    // CA-38
    public function test_si_falla_el_excel_se_vuelve_al_reporte_con_el_mensaje_y_el_reporte_sigue_en_pantalla(): void
    {
        $this->datosDeEjemplo();
        $this->mock(ReporteExcelService::class)->shouldReceive('generar')->andThrow(new RuntimeException('falla simulada'));

        $this->desde('reportes.ocupacion.excel')
            ->assertRedirect(route('reportes.ocupacion'))
            ->assertSessionHasErrors(['descarga' => self::MENSAJE_FALLO]);

        $this->get(route('reportes.ocupacion'))->assertOk()->assertSee(self::MENSAJE_FALLO)->assertSee('DEMO Grupo 1');
    }

    // RF-18 / CA-18: consultar y descargar no escribe
    public function test_consultar_y_descargar_no_escribe_en_la_base(): void
    {
        $this->datosDeEjemplo();
        $admin = $this->admin();
        $tablas = ['matriculas', 'grupos', 'inscripciones_grupo', 'cohortes', 'programas', 'modulos', 'programa_modulo', 'users'];
        $contar = fn () => array_map(fn ($tabla) => DB::table($tabla)->count(), $tablas);
        $antes = $contar();

        $escrituras = [];
        DB::listen(function ($consulta) use (&$escrituras) {
            if (preg_match('/^\s*(insert|update|delete|create|drop|alter)\b/i', $consulta->sql)) {
                $escrituras[] = $consulta->sql;
            }
        });

        $this->actingAs($admin);
        foreach (['matriculas', 'ocupacion', 'catalogo'] as $reporte) {
            foreach (['', '.pdf', '.excel'] as $sufijo) {
                $this->get(route("reportes.$reporte$sufijo"))->assertOk();
            }
        }

        $this->assertSame($antes, $contar());
        $this->assertSame([], $escrituras, 'Los reportes ejecutaron sentencias de escritura.');
    }

    // RNF-04: sin datos guardados de consultas anteriores
    public function test_los_datos_reflejan_el_momento_de_la_consulta(): void
    {
        $this->matricula();
        $admin = $this->admin();

        $primero = $this->actingAs($admin)->get(route('reportes.matriculas'))->viewData('reporte');
        $this->matricula();
        $segundo = $this->actingAs($admin)->get(route('reportes.matriculas'))->viewData('reporte');

        $this->assertSame(1, $this->totalDe($primero, 'Total de matrículas'));
        $this->assertSame(2, $this->totalDe($segundo, 'Total de matrículas'));
    }

    public function test_el_pdf_de_ocupacion_se_genera_con_muchos_grupos(): void
    {
        for ($i = 0; $i < 40; $i++) {
            $this->grupo(['cupo_minimo' => 5]);
        }

        $respuesta = $this->desde('reportes.ocupacion.pdf')->assertOk();

        $this->assertStringStartsWith('%PDF', $respuesta->getContent());
    }
}
