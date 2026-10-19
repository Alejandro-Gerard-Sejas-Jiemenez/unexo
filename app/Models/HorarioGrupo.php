<?php

namespace App\Models;

use App\Enums\DiaSemana;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $grupo_id
 * @property DiaSemana $dia_semana
 * @property string $hora_inicio
 * @property string $hora_fin
 * @property-read string $rango
 * @property-read Grupo $grupo
 */
class HorarioGrupo extends Model
{
    use HasUuids;

    protected $table = 'horarios_grupo';

    protected $fillable = [
        'grupo_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
    ];

    protected function casts(): array
    {
        return [
            'dia_semana' => DiaSemana::class,
        ];
    }

    /** Rango legible, p. ej. "19:00–21:00". */
    protected function rango(): Attribute
    {
        return Attribute::get(fn (): string => substr($this->hora_inicio, 0, 5).'–'.substr($this->hora_fin, 0, 5));
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }
}
