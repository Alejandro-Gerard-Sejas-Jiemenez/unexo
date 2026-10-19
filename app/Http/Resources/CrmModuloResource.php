<?php

namespace App\Http\Resources;

use App\Models\Modulo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso estructurado para módulos ofrecidos al CRM.
 *
 * @mixin Modulo
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
            'prerrequisito' => $this->prerrequisito,
            'pdf_temario_url' => $this->pdf_temario_url,
            'descripcion' => $this->descripcion,
            'estado_comercial' => $this->estado_comercial->value,
            'programas' => $this->programas->map(fn ($programa) => [
                'id' => $programa->id,
                'codigo' => $programa->codigo,
                'nombre' => $programa->nombre,
                'version' => $programa->version,
            ])->values(),
            'grupos_disponibles' => CrmGrupoResource::collection($this->grupos),
        ];
    }
}
