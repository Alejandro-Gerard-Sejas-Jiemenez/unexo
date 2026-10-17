<?php

namespace App\Models;

use App\Enums\EstadoPlanificacion;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cohorte extends Model
{
    use HasUuids;

    protected $table = 'cohortes';

    protected $fillable = ['programa_id', 'nombre', 'fecha_inicio', 'fecha_fin', 'estado'];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'estado' => EstadoPlanificacion::class,
        ];
    }

    public function programa(): BelongsTo
    {
        return $this->belongsTo(Programa::class);
    }

    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class);
    }

    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class);
    }

    /**
     * Módulos del programa que aún no tienen ningún grupo aperturado en esta cohorte.
     */
    public function modulosPendientes(): \Illuminate\Support\Collection
    {
        $modulosConGrupo = $this->grupos()->pluck('modulo_id')->all();

        return $this->programa->modulos->reject(fn (Modulo $m) => in_array($m->id, $modulosConGrupo))->values();
    }

    public function tieneTodosLosModulosAperturados(): bool
    {
        $totalModulos = $this->programa?->modulos()->count() ?? 0;
        if ($totalModulos === 0) {
            return false;
        }

        $modulosDistintosConGrupo = $this->grupos()->distinct('modulo_id')->count('modulo_id');

        return $modulosDistintosConGrupo >= $totalModulos;
    }
}
