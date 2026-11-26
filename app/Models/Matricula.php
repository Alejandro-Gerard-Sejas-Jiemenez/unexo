<?php

namespace App\Models;

use App\Enums\EstadoMatricula;
use App\Enums\OrigenMatricula;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Matricula extends Model
{
    use HasUuids;

    protected $table = 'matriculas';

    protected $fillable = [
        'estudiante_id',
        'cohorte_id',
        'estado',
        'origen',
        'id_externo_crm',
        'fecha_matricula',
        'fecha_retiro',
    ];

    protected function casts(): array
    {
        return [
            'fecha_matricula' => 'date',
            'fecha_retiro' => 'date',
            'estado' => EstadoMatricula::class,
            'origen' => OrigenMatricula::class,
        ];
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }

    public function cohorte(): BelongsTo
    {
        return $this->belongsTo(Cohorte::class);
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(InscripcionGrupo::class);
    }

    /**
     * Importe a cobrar: el del programa si va por una edición; si es un módulo
     * suelto, el precio de ese módulo. Null cuando todavía no tiene precio.
     */
    public function importe(): ?float
    {
        $precio = $this->cohorte_id
            ? $this->cohorte?->programa?->precio
            : $this->inscripciones->first()?->grupo?->modulo?->precio;

        return $precio !== null ? (float) $precio : null;
    }

    public function comprobantes(): HasMany
    {
        return $this->hasMany(Comprobante::class)->latest();
    }

    /** Pagos electrónicos: pagos únicos y cuotas del plan de pagos. */
    public function transacciones(): HasMany
    {
        return $this->hasMany(Transaccion::class)->orderBy('numero_cuota');
    }
}
