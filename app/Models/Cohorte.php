<?php

namespace App\Models;

use App\Enums\EstadoPlanificacion;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $programa_id
 * @property string $nombre
 * @property Carbon $fecha_inicio
 * @property Carbon $fecha_fin
 * @property EstadoPlanificacion $estado
 * @property-read Programa $programa
 */
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

    protected static function booted(): void
    {
        static::creating(function (Cohorte $cohorte) {
            if (empty($cohorte->nombre) && ! empty($cohorte->programa_id)) {
                $cohorte->nombre = static::generarNombreEdicion($cohorte->programa_id);
            }
        });
    }

    /**
     * Genera automáticamente el nombre correlativo de edición para un programa (Edición 1, Edición 2, etc.)
     */
    public static function generarNombreEdicion(string $programaId): string
    {
        $nombres = static::where('programa_id', $programaId)->pluck('nombre');

        $maxNum = 0;
        foreach ($nombres as $nombre) {
            if (preg_match('/Edici[oó]n\s+(\d+)/iu', (string) $nombre, $m)) {
                $maxNum = max($maxNum, (int) $m[1]);
            }
        }

        $siguiente = $maxNum > 0 ? $maxNum + 1 : 1;

        while ($nombres->contains("Edición {$siguiente}")) {
            $siguiente++;
        }

        return "Edición {$siguiente}";
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
