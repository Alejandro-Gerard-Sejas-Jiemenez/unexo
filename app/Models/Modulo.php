<?php

namespace App\Models;

use App\Enums\EstadoComercial;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Modulo extends Model
{
    use HasUuids;

    protected $table = 'modulos';

    protected $fillable = [
        'codigo',
        'nombre',
        'duracion_horas',
        'estado_comercial',
        'descripcion',
        'prerrequisito',
        'pdf_temario_url',
    ];

    protected function casts(): array
    {
        return [
            'estado_comercial' => EstadoComercial::class,
        ];
    }

    // Todo módulo es libre: se puede tomar suelto o dentro de un programa. El CRM ve los que están abiertos.
    public function scopeVisibleParaCrm(Builder $consulta): Builder
    {
        return $consulta->where('estado_comercial', EstadoComercial::Abierto->value);
    }

    public function programas(): BelongsToMany
    {
        return $this->belongsToMany(Programa::class, 'programa_modulo')->withPivot('orden');
    }

    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class);
    }
}
