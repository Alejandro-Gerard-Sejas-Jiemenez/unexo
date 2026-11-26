<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaccion extends Model
{
    use HasUuids;

    protected $table = 'transacciones';

    protected $fillable = [
        'matricula_id',
        'metodo_pago_id',
        'monto',
        'numero_cuota',
        'total_cuotas',
        'estado',
        'referencia',
        'fecha_proceso',
        'registrada_por',
        'proveedor',
        'moneda',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'stripe_checkout_session_id',
        'stripe_intento',
        'motivo_rechazo',
        'fecha_vencimiento',
        'metadata',
    ];

    protected $attributes = ['stripe_intento' => 0];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'numero_cuota' => 'integer',
            'total_cuotas' => 'integer',
            'fecha_proceso' => 'datetime',
            'stripe_intento' => 'integer',
            'fecha_vencimiento' => 'date',
            'metadata' => 'array',
        ];
    }

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }

    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }

    public function estaPagado(): bool
    {
        return in_array($this->estado, ['aprobada', 'reembolsada'], true);
    }

    /** Alguna vez se inició en Stripe, cualquiera haya sido el resultado. */
    public function usoStripe(): bool
    {
        return $this->stripe_intento > 0
            || $this->stripe_checkout_session_id !== null
            || $this->stripe_payment_intent_id !== null;
    }

    public function admiteResolucionManual(): bool
    {
        return in_array($this->estado, ['pendiente', 'rechazada'], true) && ! $this->usoStripe();
    }

    public function esCuota(): bool
    {
        return $this->numero_cuota !== null;
    }
}
