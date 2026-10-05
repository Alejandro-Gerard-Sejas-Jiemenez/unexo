<?php

namespace App\Services;

use App\Models\Modulo;
use App\Models\Programa;
use Illuminate\Support\Facades\DB;

/** Orden de los módulos dentro de un programa (`programa_modulo.orden`, siempre 1..n sin huecos). */
class OrdenModulosService
{
    /** Agrega el módulo al final. Devuelve false si ya estaba en el programa. */
    public function agregar(Programa $programa, string $moduloId): bool
    {
        if ($programa->modulos()->where('modulos.id', $moduloId)->exists()) {
            return false;
        }

        $orden = (int) $programa->modulos()->max('programa_modulo.orden') + 1;
        $programa->modulos()->attach($moduloId, ['orden' => $orden]);

        return true;
    }

    public function quitar(Programa $programa, Modulo $modulo): void
    {
        DB::transaction(function () use ($programa, $modulo) {
            $programa->modulos()->detach($modulo->id);
            $this->guardarOrden($programa, $programa->modulos()->pluck('modulos.id')->all());
        });
    }

    /** Intercambia el módulo con su vecino. $direccion: 'subir' o 'bajar'. */
    public function mover(Programa $programa, Modulo $modulo, string $direccion): void
    {
        DB::transaction(function () use ($programa, $modulo, $direccion) {
            $ids = $programa->modulos()->pluck('modulos.id')->all();
            $i = array_search($modulo->id, $ids, true);

            if ($i === false) {
                return;
            }

            $j = $direccion === 'subir' ? $i - 1 : $i + 1;

            if (isset($ids[$j])) {
                [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
                $this->guardarOrden($programa, $ids);
            }
        });
    }

    private function guardarOrden(Programa $programa, array $ids): void
    {
        foreach (array_values($ids) as $n => $id) {
            $programa->modulos()->updateExistingPivot($id, ['orden' => $n + 1]);
        }
    }
}
