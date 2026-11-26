<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ConsultaTexto
{
    public static function patron(string $termino): string
    {
        $escapado = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $termino);

        return "%{$escapado}%";
    }

    public static function operador(?string $driver = null): string
    {
        $driver ??= DB::connection()->getDriverName();

        return $driver === 'pgsql' ? 'ilike' : 'like';
    }

    public static function dondeContiene(Builder $consulta, string $columna, string $termino, bool $or = false): Builder
    {
        $op = self::operador($consulta->getConnection()->getDriverName());
        $patron = self::patron($termino);

        return $or
            ? $consulta->orWhere($columna, $op, $patron)
            : $consulta->where($columna, $op, $patron);
    }
}
