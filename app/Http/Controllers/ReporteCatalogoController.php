<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReporteCatalogoRequest;
use App\Services\ReporteCatalogoService;
use App\Services\ReporteExcelService;
use App\Services\ReportePdfService;
use Illuminate\View\View;

class ReporteCatalogoController extends ReporteController
{
    public function index(ReporteCatalogoRequest $request, ReporteCatalogoService $servicio): View
    {
        $filtros = [];

        return view('reportes.catalogo', [
            'filtros' => $filtros,
            'reporte' => $servicio->generar(),
        ]);
    }

    public function pdf(ReporteCatalogoRequest $request, ReporteCatalogoService $servicio, ReportePdfService $pdf)
    {
        $filtros = [];

        return $this->descargarPdf($servicio->generar(), $pdf, 'reportes.catalogo', $filtros);
    }

    public function excel(ReporteCatalogoRequest $request, ReporteCatalogoService $servicio, ReporteExcelService $excel)
    {
        $filtros = [];

        return $this->descargarExcel($servicio->generar(), $excel, 'reportes.catalogo', $filtros);
    }
}
