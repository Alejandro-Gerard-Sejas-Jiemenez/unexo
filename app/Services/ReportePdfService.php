<?php

namespace App\Services;

use App\Support\Reporte;
use Barryvdh\DomPDF\Facade\Pdf;

/** Convierte cualquier Reporte en un PDF; no conoce los reportes concretos. */
class ReportePdfService
{
    /** @return string contenido binario del PDF */
    public function generar(Reporte $reporte): string
    {
        return Pdf::loadView('reportes.pdf', ['reporte' => $reporte])
            ->setPaper('a4', $reporte->horizontal ? 'landscape' : 'portrait')
            ->output();
    }
}
