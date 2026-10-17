<?php

namespace App\Http\Controllers;

use App\Http\Requests\FiltrosReporteOcupacionRequest;
use App\Services\ReporteExcelService;
use App\Services\ReporteOcupacionService;
use App\Services\ReportePdfService;
use Illuminate\View\View;

class ReporteOcupacionController extends ReporteController
{
    public function index(FiltrosReporteOcupacionRequest $request, ReporteOcupacionService $servicio): View
    {
        $filtros = $request->filtros();

        return view('reportes.ocupacion', $request->datosFormulario() + [
            'filtros' => $filtros,
            'reporte' => $servicio->generar($filtros),
        ]);
    }

    public function pdf(FiltrosReporteOcupacionRequest $request, ReporteOcupacionService $servicio, ReportePdfService $pdf)
    {
        $filtros = $request->filtros();

        return $this->descargarPdf($servicio->generar($filtros), $pdf, 'reportes.ocupacion', $filtros);
    }

    public function excel(FiltrosReporteOcupacionRequest $request, ReporteOcupacionService $servicio, ReporteExcelService $excel)
    {
        $filtros = $request->filtros();

        return $this->descargarExcel($servicio->generar($filtros), $excel, 'reportes.ocupacion', $filtros);
    }
}
