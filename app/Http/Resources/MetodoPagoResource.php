<?php

namespace App\Http\Resources;

use App\Models\MetodoPago;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MetodoPago
 */
class MetodoPagoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'proveedor' => $this->proveedor ?? 'simulado',
            'marca' => $this->marca,
            'descripcion' => $this->descripcion(),
            'titular' => $this->titular,
            'ultimos_digitos' => $this->ultimos_digitos,
            'vencimiento' => $this->vencimiento,
            'etiqueta' => $this->etiqueta,
            'predeterminado' => $this->predeterminado,
        ];
    }
}
