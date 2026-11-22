<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetodoPago extends Model
{
    use HasUuids;

    protected $table = 'metodos_pago';

    protected $fillable = [
        'user_id',
        'tipo',
        'titular',
        'ultimos_digitos',
        'vencimiento',
        'etiqueta',
        'predeterminado',
    ];

    protected function casts(): array
    {
        return ['predeterminado' => 'boolean'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transacciones(): HasMany
    {
        return $this->hasMany(Transaccion::class, 'metodo_pago_id');
    }

    /** Cómo se nombra el método en pantalla, sin exponer datos sensibles. */
    public function descripcion(): string
    {
        return match ($this->tipo) {
            'tarjeta' => 'Tarjeta terminada en '.($this->ultimos_digitos ?? '····'),
            'qr' => 'Pago con QR',
            default => 'Transferencia bancaria',
        };
    }
}
