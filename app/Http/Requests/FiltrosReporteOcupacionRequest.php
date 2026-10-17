<?php

namespace App\Http\Requests;

use App\Enums\EstadoPlanificacion;
use App\Http\Requests\Concerns\ValidaRangoDeFechas;
use App\Models\Cohorte;
use Illuminate\Validation\Rule;

class FiltrosReporteOcupacionRequest extends FiltrosReporteRequest
{
    use ValidaRangoDeFechas;

    protected function vistaPantalla(): string
    {
        return 'reportes.ocupacion';
    }

    public function rules(): array
    {
        return [
            'estado' => ['bail', 'nullable', 'string', Rule::enum(EstadoPlanificacion::class)],
            'cohorte_id' => ['bail', 'nullable', 'uuid', 'exists:cohortes,id'],
            'desde' => ['bail', 'nullable', 'string', 'date_format:Y-m-d'],
            'hasta' => ['bail', 'nullable', 'string', 'date_format:Y-m-d'],
        ];
    }

    public function datosFormulario(): array
    {
        return [
            'estados' => EstadoPlanificacion::cases(),
            'cohortes' => Cohorte::with('programa:id,nombre')->orderBy('nombre')->orderBy('id')->get(['id', 'programa_id', 'nombre']),
        ];
    }
}
