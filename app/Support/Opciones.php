<?php

namespace App\Support;

use Illuminate\Contracts\Database\Query\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;

class Opciones
{
    /**
     * Carga como máximo `limites.opciones_select` filas para un <select>.
     * Devuelve [opciones, total] para que el formulario avise si se recortó la lista.
     *
     * @param EloquentBuilder|QueryBuilder|BuilderContract $consulta
     * @param array $columnas
     * @return array{0: Collection, 1: int}
     */
    public static function limitar(EloquentBuilder|QueryBuilder|BuilderContract $consulta, array $columnas = ['*']): array
    {
        $base = method_exists($consulta, 'toBase') ? (clone $consulta)->toBase() : (clone $consulta);
        $total = $base->getCountForPagination();

        return [$consulta->limit(config('limites.opciones_select'))->get($columnas), $total];
    }
}
