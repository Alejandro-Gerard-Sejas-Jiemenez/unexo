<?php

namespace App\Http\Resources;

use App\Models\Cohorte;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Cohorte
 */
class CohortePanelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'programa_id' => $this->programa_id,
            'programa' => $this->whenLoaded('programa', fn () => [
                'id' => $this->programa->id,
                'nombre' => $this->programa->nombre,
                'version' => $this->programa->version,
            ]),
            'fecha_inicio' => $this->fecha_inicio?->format('Y-m-d'),
            'fecha_fin' => $this->fecha_fin?->format('Y-m-d'),
            'estado' => $this->estado->value,
            'estado_label' => $this->estado->label(),
            'grupos_count' => $this->whenCounted('grupos'),
            'matriculas_count' => $this->whenCounted('matriculas'),
            'grupos' => GrupoPanelResource::collection($this->whenLoaded('grupos')),
        ];
    }
}
