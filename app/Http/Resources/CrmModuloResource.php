<?php

namespace App\Http\Resources;

use App\Enums\EstadoPlanificacion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso estructurado para módulos independientes ofrecidos al CRM.
 * @mixin \App\Models\Modulo
 */
class CrmModuloResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'duracion_horas' => $this->duracion_horas,
            'creditos' => $this->creditos,
            'tipo_evaluacion' => $this->tipo_evaluacion,
            'pdf_temario_url' => $this->pdf_temario_url,
            'descripcion' => $this->descripcion,
            'estado_comercial' => $this->estado_comercial->value,
            'programas' => $this->programas?->map(fn ($programa) => [
                'id' => $programa->id,
                'codigo' => $programa->codigo,
                'nombre' => $programa->nombre,
                'version' => $programa->version,
            ])->values() ?? [],
            'grupos_disponibles' => $this->grupos
                ?->whereNull('cohorte_id')
                ->whereIn('estado', [EstadoPlanificacion::Programado, EstadoPlanificacion::EnCurso])
                ->map(fn ($grupo) => [
                    'id' => $grupo->id,
                    'nombre' => $grupo->nombre,
                    'modalidad' => $grupo->modalidad->value,
                    'fecha_inicio' => $grupo->fecha_inicio?->toDateString(),
                    'fecha_fin' => $grupo->fecha_fin?->toDateString(),
                    'cupo_minimo' => $grupo->cupo_minimo,
                    'aula' => $grupo->aula?->nombre,
                    'docente' => $grupo->docente?->nombre_completo,
                ])->values() ?? [],
        ];
    }
}
