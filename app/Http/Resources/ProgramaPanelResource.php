<?php

namespace App\Http\Resources;

use App\Models\Programa;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Programa visto desde el panel. Es distinto del recurso que consume el CRM:
 * ahí solo viaja la oferta publicada, aquí también el borrador y su gestión.
 *
 * @mixin Programa
 */
class ProgramaPanelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'version' => $this->version,
            'descripcion' => $this->descripcion,
            'precio' => $this->precio !== null ? (float) $this->precio : null,
            'estado_comercial' => $this->estado_comercial->value,
            'estado_label' => $this->estado_comercial->label(),
            'certificado_otorga' => $this->certificado_otorga?->value,
            'certificado_label' => $this->certificado_otorga?->label(),
            'requisitos_ingreso' => $this->requisitos_ingreso,
            'imagen_portada_url' => $this->imagen_portada_url,
            'modulos_count' => $this->whenCounted('modulos'),
            'modulos' => ModuloPanelResource::collection($this->whenLoaded('modulos')),
        ];
    }
}
