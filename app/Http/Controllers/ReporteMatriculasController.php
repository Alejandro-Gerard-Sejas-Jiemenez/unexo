<?php

namespace App\Http\Controllers;

use App\Http\Requests\FiltrosReporteMatriculasRequest;
use App\Services\ReporteExcelService;
use App\Services\ReporteMatriculasService;
use App\Services\ReportePdfService;
use Illuminate\View\View;

class ReporteMatriculasController extends ReporteController
{
    public function index(FiltrosReporteMatriculasRequest $request, ReporteMatriculasService $servicio): View
    {
        $filtros = $request->filtros();

        return view('reportes.matriculas', $request->datosFormulario() + [
            'filtros' => $filtros,
            'reporte' => $servicio->generar($filtros),
        ]);
    }

    public function pdf(FiltrosReporteMatriculasRequest $request, ReporteMatriculasService $servicio, ReportePdfService $pdf)
    {
        $filtros = $request->filtros();

        return $this->descargarPdf($servicio->generar($filtros), $pdf, 'reportes.matriculas', $filtros);
    }

    public function excel(FiltrosReporteMatriculasRequest $request, ReporteMatriculasService $servicio, ReporteExcelService $excel)
    {
        $filtros = $request->filtros();

        return $this->descargarExcel($servicio->generar($filtros), $excel, 'reportes.matriculas', $filtros);
    }
}
