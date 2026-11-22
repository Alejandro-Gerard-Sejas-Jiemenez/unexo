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
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'numero_cuota' => 'integer',
            'total_cuotas' => 'integer',
            'fecha_proceso' => 'datetime',
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

    public function esCuota(): bool
    {
        return $this->numero_cuota !== null;
    }
}
