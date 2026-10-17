<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidaRangoDeFechas;
use App\Models\Cohorte;
use App\Models\Programa;

class FiltrosReporteMatriculasRequest extends FiltrosReporteRequest
{
    use ValidaRangoDeFechas;

    protected function vistaPantalla(): string
    {
        return 'reportes.matriculas';
    }

    public function rules(): array
    {
        return [
            'desde' => ['bail', 'nullable', 'string', 'date_format:Y-m-d'],
            'hasta' => ['bail', 'nullable', 'string', 'date_format:Y-m-d'],
            'programa_id' => ['bail', 'nullable', 'uuid', 'exists:programas,id'],
            'cohorte_id' => ['bail', 'nullable', 'uuid', 'exists:cohortes,id'],
        ];
    }

    public function datosFormulario(): array
    {
        return [
            'programas' => Programa::orderBy('nombre')->orderBy('id')->get(['id', 'codigo', 'nombre']),
            'cohortes' => Cohorte::with('programa:id,nombre')->orderBy('nombre')->orderBy('id')->get(['id', 'programa_id', 'nombre']),
        ];
    }
}
