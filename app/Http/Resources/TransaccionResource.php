<?php

namespace App\Http\Resources;

use App\Models\Transaccion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Transaccion
 */
class TransaccionResource extends JsonResource
{
    private const ESTADOS = [
        'pendiente' => 'Pendiente',
        'aprobada' => 'Aprobada',
        'rechazada' => 'Rechazada',
    ];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'matricula_id' => $this->matricula_id,
            'monto' => (float) $this->monto,
            'numero_cuota' => $this->numero_cuota,
            'total_cuotas' => $this->total_cuotas,
            'concepto' => $this->esCuota()
                ? "Cuota {$this->numero_cuota} de {$this->total_cuotas}"
                : 'Pago único',
            'estado' => $this->estado,
            'estado_label' => self::ESTADOS[$this->estado] ?? $this->estado,
            'referencia' => $this->referencia,
            'metodo' => $this->whenLoaded('metodoPago', fn () => $this->metodoPago?->descripcion()),
            'fecha_proceso' => $this->fecha_proceso?->format('d/m/Y H:i'),
        ];
    }
}
