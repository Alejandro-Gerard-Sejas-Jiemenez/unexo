<?php

namespace App\Models;

use App\Enums\Certificado;
use App\Enums\EstadoComercial;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'imagen_portada_url',
        'requisitos_ingreso',
        'certificado_otorga',
    ];

    /** Tolerante: un texto antiguo (previo al Enum) se lee como "no definido" en vez de fallar. */
    protected function certificadoOtorga(): Attribute
    {
        return Attribute::make(
            get: fn (?string $valor) => $valor === null ? null : Certificado::tryFrom($valor),
            set: fn (Certificado|string|null $valor) => $valor instanceof Certificado ? $valor->value : $valor,
        );
    }

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
            fn (Builder $modulos) => $modulos->reorder()->where('modulos.estado_comercial', EstadoComercial::Abierto->value),
        );
    }

    public function scopeAbiertoSinModulosAbiertos(Builder $consulta): Builder
    {
        return $consulta->abierto()->whereDoesntHave(
            'modulos',
            fn (Builder $modulos) => $modulos->reorder()->where('modulos.estado_comercial', EstadoComercial::Abierto->value),
        );
    }

    public function modulos(): BelongsToMany
    {
        return $this->belongsToMany(Modulo::class, 'programa_modulo')
            ->withPivot('orden')
            ->orderBy('programa_modulo.orden');
    }

    public function cohortes(): HasMany
    {
        return $this->hasMany(Cohorte::class);
    }
}
