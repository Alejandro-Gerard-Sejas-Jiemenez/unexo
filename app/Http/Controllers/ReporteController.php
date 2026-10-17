<?php

namespace App\Http\Controllers;

use App\Services\ReporteExcelService;
use App\Services\ReportePdfService;
use App\Support\Reporte;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/** Descargas compartidas de los reportes: si el archivo no se puede generar, se vuelve a la pantalla con el aviso. */
abstract class ReporteController extends Controller
{
    public const MENSAJE_FALLO = 'No se pudo generar el archivo. Intenta de nuevo.';

    protected function descargarPdf(Reporte $reporte, ReportePdfService $pdf, string $rutaPantalla, array $filtros): Response|RedirectResponse
    {
        try {
            $contenido = $pdf->generar($reporte);
        } catch (Throwable $e) {
            return $this->falloDeDescarga($e, $rutaPantalla, $filtros);
        }

        return response($contenido, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$reporte->nombreArchivo('pdf').'"',
        ]);
    }

    protected function descargarExcel(Reporte $reporte, ReporteExcelService $excel, string $rutaPantalla, array $filtros): BinaryFileResponse|RedirectResponse
    {
        try {
            $ruta = $excel->generar($reporte);
        } catch (Throwable $e) {
            return $this->falloDeDescarga($e, $rutaPantalla, $filtros);
        }

        return response()->download($ruta, $reporte->nombreArchivo('xlsx'), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function falloDeDescarga(Throwable $e, string $rutaPantalla, array $filtros): RedirectResponse
    {
        report($e);

        return redirect()->route($rutaPantalla, $filtros)->withErrors(['descarga' => self::MENSAJE_FALLO]);
    }
}
