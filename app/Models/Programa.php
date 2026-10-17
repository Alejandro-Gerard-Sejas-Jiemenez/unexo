<?php

namespace App\Models;

use App\Enums\EstadoComercial;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Programa extends Model
{
    use HasUuids;

    protected $table = 'programas';

    protected $fillable = [
        'codigo',
        'nombre',
        'version',
        'descripcion',
        'estado_comercial',
        'precio',
        'nivel_academico',
        'imagen_portada_url',
        'requisitos_ingreso',
        'perfil_egreso',
        'certificado_otorga',
    ];

    protected function casts(): array
    {
        return ['estado_comercial' => EstadoComercial::class];
    }

    public function scopeAbierto(Builder $consulta): Builder
    {
        return $consulta->where('estado_comercial', EstadoComercial::Abierto->value);
    }

    /** Programas que el CRM ve con contenido: abiertos y con al menos un módulo abierto. */
    public function scopeVisibleParaCrm(Builder $consulta): Builder
    {
        return $consulta->abierto()->whereHas(
            'modulos',
            fn(Builder $modulos) => $modulos->reorder()->where('modulos.estado_comercial', EstadoComercial::Abierto->value),
        );
    }

    public function scopeAbiertoSinModulosAbiertos(Builder $consulta): Builder
    {
        return $consulta->abierto()->whereDoesntHave(
            'modulos',
            fn(Builder $modulos) => $modulos->reorder()->where('modulos.estado_comercial', EstadoComercial::Abierto->value),
        );
    }

    public function modulos(): BelongsToMany
    {
        return $this->belongsToMany(Modulo::class, 'programa_modulo')
            ->withPivot('orden')
            ->orderBy('programa_modulo.orden');
    }
}
