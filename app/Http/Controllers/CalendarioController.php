<?php

namespace App\Http\Controllers;

use App\Enums\Rol;
use App\Services\MisClasesService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CalendarioController extends Controller
{
    public function index(Request $request, MisClasesService $clases): View
    {
        $usuario = $request->user();

        $grupos = $usuario->rol === Rol::Docente
            ? $clases->gruposDelDocente($usuario)
            : $clases->inscripcionesDelEstudiante($usuario)->map(fn ($inscripcion) => $inscripcion->grupo);

        $mes = $this->mesSolicitado($request->query('mes'));
        $eventos = $this->eventosDeGrupos($grupos);

        $eventosDelMes = $eventos
            ->filter(fn ($e) => $e['fecha']->isSameMonth($mes))
            ->sortBy(fn ($e) => $e['fecha']->toDateString())
            ->values();

        return view('calendario.index', [
            'mes' => $mes,
            'semanas' => $this->semanas($mes, $eventos),
            'agenda' => $eventosDelMes,
            'mesAnterior' => $mes->subMonth()->format('Y-m'),
            'mesSiguiente' => $mes->addMonth()->format('Y-m'),
        ]);
    }

    private function mesSolicitado(?string $valor): CarbonImmutable
    {
        if ($valor && preg_match('/^\d{4}-\d{2}$/', $valor)) {
            try {
                return CarbonImmutable::createFromFormat('Y-m-d', $valor.'-01')->startOfMonth();
            } catch (\Throwable) {
                // Valor con formato correcto pero fecha inválida: cae al mes actual.
            }
        }

        return CarbonImmutable::now()->startOfMonth();
    }

    /** Un evento de inicio y uno de fin por grupo, con las fechas del período efectivo, el módulo y el grupo. */
    private function eventosDeGrupos(Collection $grupos): Collection
    {
        return $grupos
            ->filter(fn ($grupo) => $grupo->periodo_inicio && $grupo->periodo_fin)
            ->flatMap(function ($grupo) {
                $titulo = $grupo->modulo->nombre.' · '.$grupo->nombre;

                return [
                    ['fecha' => CarbonImmutable::parse($grupo->periodo_inicio), 'tipo' => 'inicio', 'texto' => 'Inicia: '.$titulo],
                    ['fecha' => CarbonImmutable::parse($grupo->periodo_fin), 'tipo' => 'fin', 'texto' => 'Termina: '.$titulo],
                ];
            });
    }

    /** Matriz de semanas (lunes a domingo) con los días del mes y sus eventos. */
    private function semanas(CarbonImmutable $mes, Collection $eventos): array
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
}
