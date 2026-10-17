<?php

namespace App\Http\Requests;

/** El reporte de catálogo no tiene filtros: esta clase solo autoriza. */
class ReporteCatalogoRequest extends FiltrosReporteRequest
{
    protected function vistaPantalla(): string
    {
        return 'reportes.catalogo';
    }

    public function rules(): array
    {
        return [];
    }
}
