<?php

namespace App\Services;

use App\Enums\EstadoMatricula;
use App\Enums\OrigenMatricula;
use App\Models\Cohorte;
use App\Models\Programa;
use App\Support\Reporte;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Reporte de solo lectura: matrículas por estado, origen y cohorte. */
class ReporteMatriculasService
{
    /** @param array{desde?: string, hasta?: string, programa_id?: string, cohorte_id?: string} $filtros ya validados */
    public function generar(array $filtros = []): Reporte
    {
        $generadoEn = Reporte::ahora();
        $total = (int) $this->consulta($filtros)->count();

        $secciones = [];
        if ($total > 0) {
            $secciones = [
                $this->seccionPorEstado($filtros, $total),
                $this->seccionPorOrigen($filtros, $total),
                $this->seccionPorCohorte($filtros, $total),
            ];
        }

        return new Reporte(
            titulo: 'Reporte de matrículas',
            archivo: 'matriculas',
            generadoEn: $generadoEn,
            filtros: $this->describirFiltros($filtros),
            totales: [['etiqueta' => 'Total de matrículas', 'valor' => $total]],
            secciones: $secciones,
            sinDatos: $total === 0,
        );
    }

    private function consulta(array $filtros): Builder
    {
        $consulta = DB::table('matriculas');

        if (isset($filtros['desde'])) {
            $consulta->whereDate('matriculas.fecha_matricula', '>=', $filtros['desde']);
        }
        if (isset($filtros['hasta'])) {
            $consulta->whereDate('matriculas.fecha_matricula', '<=', $filtros['hasta']);
        }
        if (isset($filtros['cohorte_id'])) {
            $consulta->where('matriculas.cohorte_id', $filtros['cohorte_id']);
        }
        if (isset($filtros['programa_id'])) {
            $consulta->whereIn('matriculas.cohorte_id', fn (Builder $cohortes) => $cohortes
                ->select('id')->from('cohortes')->where('programa_id', $filtros['programa_id']));
        }

        return $consulta;
    }

    private function seccionPorEstado(array $filtros, int $total): array
    {
        $cantidades = $this->consulta($filtros)
            ->selectRaw('matriculas.estado as clave, count(*) as cantidad')
            ->groupBy('matriculas.estado')
            ->pluck('cantidad', 'clave');

        $filas = array_map(fn (EstadoMatricula $estado) => $this->fila($estado->label(), (int) ($cantidades[$estado->value] ?? 0), $total), EstadoMatricula::cases());

        return $this->seccion('Matrículas por estado', 'Estado', $filas);
    }

    private function seccionPorOrigen(array $filtros, int $total): array
    {
        $cantidades = $this->consulta($filtros)
            ->selectRaw('matriculas.origen as clave, count(*) as cantidad')
            ->groupBy('matriculas.origen')
            ->pluck('cantidad', 'clave');

        $filas = array_map(fn (OrigenMatricula $origen) => $this->fila($origen->label(), (int) ($cantidades[$origen->value] ?? 0), $total), OrigenMatricula::cases());

        return $this->seccion('Matrículas por origen', 'Origen', $filas);
    }

    private function seccionPorCohorte(array $filtros, int $total): array
    {
        $grupos = $this->consulta($filtros)
            ->leftJoin('cohortes', 'cohortes.id', '=', 'matriculas.cohorte_id')
            ->leftJoin('programas', 'programas.id', '=', 'cohortes.programa_id')
            ->selectRaw('matriculas.cohorte_id, cohortes.nombre as cohorte, programas.nombre as programa, count(*) as cantidad')
            ->groupBy('matriculas.cohorte_id', 'cohortes.nombre', 'programas.nombre')
            ->orderByRaw('matriculas.cohorte_id is null')
            ->orderBy('programas.nombre')
            ->orderBy('cohortes.nombre')
            ->orderBy('matriculas.cohorte_id')
            ->get();

        $filas = $grupos->map(fn ($grupo) => [
            $grupo->cohorte_id === null ? null : $grupo->programa,
            $grupo->cohorte_id === null ? 'Sin cohorte' : $grupo->cohorte,
            (int) $grupo->cantidad,
            Reporte::porcentaje((int) $grupo->cantidad, $total),
        ])->all();

        return [
            'titulo' => 'Matrículas por cohorte',
            'columnas' => [
                ['titulo' => 'Programa', 'tipo' => 'texto'],
                ['titulo' => 'Cohorte', 'tipo' => 'texto'],
                ['titulo' => 'Cantidad', 'tipo' => 'entero'],
                ['titulo' => 'Porcentaje', 'tipo' => 'porcentaje'],
            ],
            'filas' => $filas,
        ];
    }

    private function fila(string $etiqueta, int $cantidad, int $total): array
    {
        return [$etiqueta, $cantidad, Reporte::porcentaje($cantidad, $total)];
    }

    private function seccion(string $titulo, string $primeraColumna, array $filas): array
    {
        return [
            'titulo' => $titulo,
            'columnas' => [
                ['titulo' => $primeraColumna, 'tipo' => 'texto'],
                ['titulo' => 'Cantidad', 'tipo' => 'entero'],
                ['titulo' => 'Porcentaje', 'tipo' => 'porcentaje'],
            ],
            'filas' => $filas,
        ];
    }

    private function describirFiltros(array $filtros): array
    {
        $fecha = fn (?string $valor) => $valor ? Carbon::createFromFormat('Y-m-d', $valor)->format('d/m/Y') : 'Todos';

        return [
            ['etiqueta' => 'Fecha de matrícula desde', 'valor' => $fecha($filtros['desde'] ?? null)],
            ['etiqueta' => 'Fecha de matrícula hasta', 'valor' => $fecha($filtros['hasta'] ?? null)],
            ['etiqueta' => 'Programa', 'valor' => isset($filtros['programa_id']) ? Programa::find($filtros['programa_id'])?->nombre ?? 'Todos' : 'Todos'],
            ['etiqueta' => 'Cohorte', 'valor' => isset($filtros['cohorte_id']) ? Cohorte::find($filtros['cohorte_id'])?->nombre ?? 'Todos' : 'Todos'],
        ];
    }
}
