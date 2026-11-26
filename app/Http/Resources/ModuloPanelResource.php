<?php

namespace App\Http\Resources;

use App\Models\Modulo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Modulo
 */
class ModuloPanelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'prerrequisito' => $this->prerrequisito,
            'duracion_horas' => $this->duracion_horas,
            'precio' => $this->precio !== null ? (float) $this->precio : null,
            'estado_comercial' => $this->estado_comercial->value,
            'estado_label' => $this->estado_comercial->label(),
            'pdf_temario_url' => $this->pdf_temario_url,
            'programas_count' => $this->whenCounted('programas'),
            // Orden dentro del plan de estudio, cuando viene por la relación.
            'orden' => $this->whenPivotLoaded('programa_modulo', fn () => $this->pivot->orden),
        ];
    }
}
