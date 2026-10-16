<?php

namespace App\Models;

use App\Enums\EstadoComprobante;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comprobante extends Model
{
    use HasUuids;

    protected $table = 'comprobantes_pago';

    protected $fillable = [
        'matricula_id',
        'archivo_ruta',
        'estado',
        'subido_por',
        'verificado_por',
        'fecha_verificacion',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoComprobante::class,
            'fecha_verificacion' => 'datetime',
        ];
    }

    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    public function verificadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verificado_por');
    }
}