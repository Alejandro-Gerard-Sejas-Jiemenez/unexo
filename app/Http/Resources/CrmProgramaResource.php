<?php

namespace App\Http\Resources;

use App\Models\Programa;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso estructurado para la oferta comercial de programas hacia el CRM.
 *
 * @mixin Programa
 */
class CrmProgramaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $modulos = $this->modulos;
        $certificado = $this->certificado_otorga;

        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'version' => $this->version,
            'descripcion' => $this->descripcion,
            'precio' => $this->precio !== null ? (float) $this->precio : null,
            'moneda' => 'BOB',
            'requisitos_ingreso' => $this->requisitos_ingreso,
            'certificado_otorga' => $certificado?->value ?? 'no_definido',
            'certificado_otorga_label' => $certificado?->label() ?? 'No definido',
            'imagen_portada_url' => $this->imagen_portada_url,
            'estado_comercial' => $this->estado_comercial->value,
            'total_modulos' => $modulos->count(),
            'duracion_total_horas' => (int) $modulos->sum('duracion_horas'),
            'modulos' => $modulos->map(fn ($modulo) => [
                'id' => $modulo->id,
                'codigo' => $modulo->codigo,
                'nombre' => $modulo->nombre,
                'orden' => $modulo->pivot?->orden ?? 1,
                'duracion_horas' => $modulo->duracion_horas,
                'prerrequisito' => $modulo->prerrequisito,
                'pdf_temario_url' => $modulo->pdf_temario_url,
                'descripcion' => $modulo->descripcion,
            ])->values(),
            'ediciones_disponibles' => $this->cohortes
                ->map(fn ($cohorte) => (new CrmEdicionResource($cohorte))->sinPrograma()->toArray($request))
                ->values(),
        ];
    }
}
