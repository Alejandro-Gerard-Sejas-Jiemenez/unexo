<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\FiltrosReporteMatriculasRequest;
use App\Http\Requests\FiltrosReporteOcupacionRequest;
use App\Http\Requests\ReporteCatalogoRequest;
use App\Services\ReporteCatalogoService;
use App\Services\ReporteExcelService;
use App\Services\ReporteMatriculasService;
use App\Services\ReporteOcupacionService;
use App\Services\ReportePdfService;
use App\Support\Reporte;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ReporteApiController extends Controller
{
    public function matriculas(
        FiltrosReporteMatriculasRequest $request,
        ReporteMatriculasService $servicio,
    ): JsonResponse {
        return $this->responder(
            $servicio->generar($request->filtros()),
            $request->datosFormulario(),
        );
    }

    public function matriculasPdf(
        FiltrosReporteMatriculasRequest $request,
        ReporteMatriculasService $servicio,
        ReportePdfService $pdf,
    ): Response|JsonResponse {
        return $this->descargarPdf($servicio->generar($request->filtros()), $pdf);
    }

    public function matriculasExcel(
        FiltrosReporteMatriculasRequest $request,
        ReporteMatriculasService $servicio,
        ReporteExcelService $excel,
    ): BinaryFileResponse|JsonResponse {
        return $this->descargarExcel($servicio->generar($request->filtros()), $excel);
    }

    public function ocupacion(
        FiltrosReporteOcupacionRequest $request,
        ReporteOcupacionService $servicio,
    ): JsonResponse {
        return $this->responder(
            $servicio->generar($request->filtros()),
            $request->datosFormulario(),
        );
    }

    public function ocupacionPdf(
        FiltrosReporteOcupacionRequest $request,
        ReporteOcupacionService $servicio,
        ReportePdfService $pdf,
    ): Response|JsonResponse {
        return $this->descargarPdf($servicio->generar($request->filtros()), $pdf);
    }

    public function ocupacionExcel(
        FiltrosReporteOcupacionRequest $request,
        ReporteOcupacionService $servicio,
        ReporteExcelService $excel,
    ): BinaryFileResponse|JsonResponse {
        return $this->descargarExcel($servicio->generar($request->filtros()), $excel);
    }

    public function catalogo(
        ReporteCatalogoRequest $request,
        ReporteCatalogoService $servicio,
    ): JsonResponse {
        return $this->responder($servicio->generar(), $request->datosFormulario());
    }

    public function catalogoPdf(
        ReporteCatalogoRequest $request,
        ReporteCatalogoService $servicio,
        ReportePdfService $pdf,
    ): Response|JsonResponse {
        return $this->descargarPdf($servicio->generar(), $pdf);
    }

    public function catalogoExcel(
        ReporteCatalogoRequest $request,
        ReporteCatalogoService $servicio,
        ReporteExcelService $excel,
    ): BinaryFileResponse|JsonResponse {
        return $this->descargarExcel($servicio->generar(), $excel);
    }

    private function descargarPdf(Reporte $reporte, ReportePdfService $pdf): Response|JsonResponse
    {
        try {
            return response($pdf->generar($reporte), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$reporte->nombreArchivo('pdf').'"',
            ]);
        } catch (Throwable $e) {
            Log::warning('Error al generar PDF: '.$e->getMessage());

            return response()->json(['message' => 'No pudimos generar el archivo PDF en este momento.'], 500);
        }
    }

    private function descargarExcel(Reporte $reporte, ReporteExcelService $excel): BinaryFileResponse|JsonResponse
    {
        try {
            return response()->download($excel->generar($reporte), $reporte->nombreArchivo('xlsx'), [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        } catch (Throwable $e) {
            Log::warning('Error al generar Excel: '.$e->getMessage());

            return response()->json(['message' => 'No pudimos generar el archivo Excel en este momento.'], 500);
        }
    }

    private function responder(Reporte $reporte, array $extra): JsonResponse
    {
        return response()->json([
            'titulo' => $reporte->titulo,
            'generado_en' => $reporte->fechaGeneracion(),
            'sin_datos' => $reporte->sinDatos,
            'aviso_sin_datos' => Reporte::AVISO_SIN_DATOS,
            'filtros_aplicados' => $reporte->filtros,
            'totales' => $reporte->totales,
            'secciones' => array_map(fn (array $seccion) => [
                'titulo' => $seccion['titulo'],
                'columnas' => $seccion['columnas'],
                'vacio' => $seccion['vacio'] ?? null,
                'filas' => array_map(
                    fn (array $fila) => array_map(
                        fn (mixed $celda, int $i) => Reporte::formatear(
                            $celda,
                            $seccion['columnas'][$i]['tipo'] ?? 'texto',
                        ),
                        $fila,
                        array_keys($fila),
                    ),
                    $seccion['filas'],
                ),
            ], $reporte->secciones),
            'opciones' => $extra,
        ]);
    }
}
