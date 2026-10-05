<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class Opciones
{
    /**
     * Carga como máximo `limites.opciones_select` filas para un <select>.
     * Devuelve [opciones, total] para que el formulario avise si se recortó la lista.
     *
     * @return array{0: Collection, 1: int}
     */
    public static function limitar(Builder $consulta, array $columnas = ['*']): array
    {
        $total = (clone $consulta)->toBase()->getCountForPagination();

        return [$consulta->limit(config('limites.opciones_select'))->get($columnas), $total];
    }
}
