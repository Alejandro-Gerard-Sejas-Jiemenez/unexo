<?php

namespace App\Services;

use App\Enums\Rol;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Agenda de clases del docente y del estudiante. Vivía privada en el
 * controlador Blade; ahora la comparten esa pantalla y la API de la SPA.
 */
class CalendarioService
{
    public function __construct(private readonly MisClasesService $clases) {}

    /** Los grupos que le tocan a quien consulta, según su rol. */
    public function gruposDe(User $usuario): Collection
    {
        return $usuario->rol === Rol::Docente
            ? $this->clases->gruposDelDocente($usuario)
            : $this->clases->inscripcionesDelEstudiante($usuario)->map(fn ($i) => $i->grupo);
    }

    /** Mes pedido por el usuario; si el valor no sirve, el mes actual. */
    public function mesSolicitado(?string $valor): CarbonImmutable
    {
        if ($valor && preg_match('/^\d{4}-\d{2}$/', $valor)) {
            try {
                return CarbonImmutable::createFromFormat('Y-m-d', $valor.'-01')->startOfMonth();
            } catch (\Throwable) {
                // Formato correcto pero fecha inválida: cae al mes actual.
            }
        }

        return CarbonImmutable::now()->startOfMonth();
    }

    /** Un evento de inicio y uno de fin por grupo, con su período efectivo. */
    public function eventosDeGrupos(Collection $grupos): Collection
    {
        return $grupos
            ->filter(fn ($grupo) => $grupo->periodo_inicio && $grupo->periodo_fin)
            ->flatMap(function ($grupo) {
                $titulo = $grupo->modulo->nombre.' · '.$grupo->nombre;

                return [
                    [
                        'fecha' => CarbonImmutable::parse($grupo->periodo_inicio),
                        'tipo' => 'inicio',
                        'texto' => 'Inicia: '.$titulo,
                    ],
                    [
                        'fecha' => CarbonImmutable::parse($grupo->periodo_fin),
                        'tipo' => 'fin',
                        'texto' => 'Termina: '.$titulo,
                    ],
                ];
            });
    }

    /** Matriz de semanas (lunes a domingo) con los días del mes y sus eventos. */
    public function semanas(CarbonImmutable $mes, Collection $eventos): array
    {
        $porDia = $eventos->groupBy(fn ($e) => $e['fecha']->toDateString());

        $dia = $mes->startOfWeek(CarbonImmutable::MONDAY);
        $fin = $mes->endOfMonth()->endOfWeek(CarbonImmutable::SUNDAY);
        $hoy = CarbonImmutable::today();

        $semanas = [];

        while ($dia <= $fin) {
            $semana = [];

            for ($i = 0; $i < 7; $i++) {
                $semana[] = [
                    'numero' => $dia->day,
                    'delMes' => $dia->isSameMonth($mes),
                    'esHoy' => $dia->isSameDay($hoy),
                    'eventos' => $porDia->get($dia->toDateString(), collect()),
                ];
                $dia = $dia->addDay();
            }

            $semanas[] = $semana;
        }

        return $semanas;
    }

    /** Los eventos del mes pedido, ordenados, para la agenda lateral. */
    public function agendaDelMes(Collection $eventos, CarbonImmutable $mes): Collection
    {
        return $eventos
            ->filter(fn ($e) => $e['fecha']->isSameMonth($mes))
            ->sortBy(fn ($e) => $e['fecha']->toDateString())
            ->values();
    }
}
