<?php

namespace App\Services;

use App\Models\HorarioGrupo;
use Illuminate\Support\Collection;

/** Bloques de horario ya usados en los grupos. Se calculan: no se guardan aparte. */
class HorarioFrecuenteService
{
    /** @return list<array{dias: list<int>, hora_inicio: string, hora_fin: string, usos: int}> */
    public function masUsados(int $limite = 6): array
    {
        return HorarioGrupo::query()
            ->toBase()
            ->get(['grupo_id', 'dia_semana', 'hora_inicio', 'hora_fin'])
            // Un bloque son los días de un grupo que comparten las mismas horas.
            ->groupBy(fn (object $h) => $h->grupo_id.'|'.substr($h->hora_inicio, 0, 5).'|'.substr($h->hora_fin, 0, 5))
            ->map(fn (Collection $filas) => [
                'dias' => $filas->map(fn (object $h) => (int) $h->dia_semana)->unique()->sort()->values()->all(),
                'hora_inicio' => substr($filas->first()->hora_inicio, 0, 5),
                'hora_fin' => substr($filas->first()->hora_fin, 0, 5),
            ])
            ->groupBy(fn (array $b) => implode(',', $b['dias']).'|'.$b['hora_inicio'].'|'.$b['hora_fin'])
            ->map(fn (Collection $iguales) => $iguales->first() + ['usos' => $iguales->count()])
            ->sortBy([['usos', 'desc'], ['dias', 'asc'], ['hora_inicio', 'asc']])
            ->take($limite)
            ->values()
            ->all();
    }
}
