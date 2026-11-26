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
        'procesando' => 'En curso',
        'requiere_accion' => 'Requiere confirmación',
        'aprobada' => 'Aprobada',
        'rechazada' => 'Rechazada',
        'reembolsada' => 'Reembolsada',
        'cancelada' => 'Cancelada',
    ];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'matricula_id' => $this->matricula_id,
            'proveedor' => $this->proveedor ?? 'simulado',
            'moneda' => strtoupper((string) ($this->moneda ?? 'bob')),
            'monto' => (float) $this->monto,
            'numero_cuota' => $this->numero_cuota,
            'total_cuotas' => $this->total_cuotas,
            'concepto' => $this->esCuota()
                ? "Cuota {$this->numero_cuota} de {$this->total_cuotas}"
                : 'Pago único',
            'estado' => $this->estado,
            'estado_label' => self::ESTADOS[$this->estado] ?? $this->estado,
            'referencia' => $this->referencia,
            'puede_resolver_manual' => $this->admiteResolucionManual(),
            'motivo_rechazo' => $this->motivo_rechazo,
            'fecha_vencimiento' => $this->fecha_vencimiento?->format('Y-m-d'),
            'metodo' => $this->whenLoaded('metodoPago', fn () => $this->metodoPago?->descripcion()),
            'fecha_proceso' => $this->fecha_proceso?->format('d/m/Y H:i'),
        ];
    }
}
