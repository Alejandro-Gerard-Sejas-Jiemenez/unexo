<?php

namespace App\Services;

use App\Enums\EstadoMatricula;
use App\Enums\EstadoPlanificacion;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Support\Reporte;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/** Reporte de solo lectura: inscritos de cada grupo frente a su cupo mínimo. */
class ReporteOcupacionService
{
    public const SIN_CUPO = 'Sin cupo definido';

    public const ALCANZA = 'Alcanza el mínimo';

    public const NO_ALCANZA = 'No alcanza el mínimo';

    /** @param array{estado?: string, cohorte_id?: string, desde?: string, hasta?: string} $filtros ya validados */
    public function generar(array $filtros = []): Reporte
    {
        $generadoEn = Reporte::ahora();
        $grupos = $this->grupos($filtros);

        $filas = [];
        $noAlcanzan = 0;
        foreach ($grupos as $grupo) {
            $indicador = $this->indicador($grupo);
            $noAlcanzan += $indicador === self::NO_ALCANZA ? 1 : 0;

            $filas[] = [
                $grupo->nombre,
                $grupo->modulo->nombre,
                $grupo->cohorte?->nombre ?? 'Sin cohorte',
                trim("{$grupo->docente->nombres} {$grupo->docente->apellidos}"),
                $grupo->fecha_inicio,
                $grupo->fecha_fin,
                $grupo->estado->label(),
                (int) $grupo->inscritos,
                $grupo->cupo_minimo,
                $indicador,
            ];
        }

        $cantidad = count($filas);

        return new Reporte(
            titulo: 'Reporte de ocupación de grupos',
            archivo: 'ocupacion-grupos',
            generadoEn: $generadoEn,
            filtros: $this->describirFiltros($filtros),
            totales: [
                ['etiqueta' => 'Grupos listados', 'valor' => $cantidad],
                ['etiqueta' => 'No alcanzan el cupo mínimo', 'valor' => $noAlcanzan],
            ],
            secciones: $cantidad === 0 ? [] : [[
                'titulo' => 'Grupos',
                'columnas' => [
                    ['titulo' => 'Grupo', 'tipo' => 'texto'],
                    ['titulo' => 'Módulo', 'tipo' => 'texto'],
                    ['titulo' => 'Cohorte', 'tipo' => 'texto'],
                    ['titulo' => 'Docente', 'tipo' => 'texto'],
                    ['titulo' => 'Inicio', 'tipo' => 'fecha'],
                    ['titulo' => 'Fin', 'tipo' => 'fecha'],
                    ['titulo' => 'Estado', 'tipo' => 'texto'],
                    ['titulo' => 'Inscritos', 'tipo' => 'entero'],
                    ['titulo' => 'Cupo mínimo', 'tipo' => 'entero'],
                    ['titulo' => 'Cupo', 'tipo' => 'texto'],
                ],
                'filas' => $filas,
            ]],
            sinDatos: $cantidad === 0,
            horizontal: true,
        );
    }

    private function grupos(array $filtros)
    {
        $estadosInscritos = array_map(fn (EstadoMatricula $estado) => $estado->value, EstadoMatricula::queCuentanComoInscritos());

        return Grupo::query()
            // Del docente solo se carga el nombre: el reporte no debe tocar su correo ni su documento.
            ->with(['modulo:id,nombre', 'cohorte:id,nombre', 'docente:id,nombres,apellidos'])
            ->withCount(['inscripciones as inscritos' => fn (Builder $inscripciones) => $inscripciones
                ->whereHas('matricula', fn (Builder $matricula) => $matricula->whereIn('estado', $estadosInscritos))])
            ->when($filtros['estado'] ?? null, fn (Builder $q, string $estado) => $q->where('estado', $estado))
            ->when($filtros['cohorte_id'] ?? null, fn (Builder $q, string $cohorte) => $q->where('cohorte_id', $cohorte))
            // El período del grupo se solapa con el rango; ambos extremos incluidos.
            ->when($filtros['desde'] ?? null, fn (Builder $q, string $desde) => $q->whereDate('fecha_fin', '>=', $desde))
            ->when($filtros['hasta'] ?? null, fn (Builder $q, string $hasta) => $q->whereDate('fecha_inicio', '<=', $hasta))
            ->orderByDesc('fecha_inicio')
            ->orderBy('nombre')
            ->orderBy('id')
            ->get();
    }

    /** Sin cupo, luego cancelado (sin indicador), luego alcanza o no alcanza. */
    private function indicador(Grupo $grupo): ?string
    {
        $cupo = (int) ($grupo->cupo_minimo ?? 0);

        return match (true) {
            $cupo <= 0 => self::SIN_CUPO,
            $grupo->estado === EstadoPlanificacion::Cancelado => null,
            (int) $grupo->inscritos >= $cupo => self::ALCANZA,
            default => self::NO_ALCANZA,
        };
    }

    private function describirFiltros(array $filtros): array
    {
        $fecha = fn (?string $valor) => $valor ? Carbon::createFromFormat('Y-m-d', $valor)->format('d/m/Y') : 'Todos';

        return [
            ['etiqueta' => 'Estado del grupo', 'valor' => isset($filtros['estado']) ? EstadoPlanificacion::from($filtros['estado'])->label() : 'Todos'],
            ['etiqueta' => 'Cohorte', 'valor' => isset($filtros['cohorte_id']) ? Cohorte::find($filtros['cohorte_id'])?->nombre ?? 'Todos' : 'Todos'],
            ['etiqueta' => 'Período desde', 'valor' => $fecha($filtros['desde'] ?? null)],
            ['etiqueta' => 'Período hasta', 'valor' => $fecha($filtros['hasta'] ?? null)],
        ];
    }
}
