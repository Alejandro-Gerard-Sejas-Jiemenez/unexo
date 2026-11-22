<?php

namespace App\Services;

use App\Enums\EstadoPlanificacion;
use App\Models\Cohorte;
use App\Models\Grupo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Listados de ediciones y grupos con sus filtros. Los comparten el panel Blade
 * y la API del panel, para que los dos filtren y ordenen igual.
 */
class PlanificacionConsultaService
{
    /**
     * @param  array{estado?: ?string, q?: ?string}  $filtros
     */
    public function cohortes(array $filtros): LengthAwarePaginator
    {
        $estado = EstadoPlanificacion::tryFrom((string) ($filtros['estado'] ?? ''));
        $busqueda = trim((string) ($filtros['q'] ?? ''));

        return Cohorte::with('programa')
            ->withCount(['grupos', 'matriculas'])
            ->when($estado, fn ($q, $e) => $q->where('estado', $e->value))
            ->when($busqueda !== '', fn ($q) => $q->where(fn ($b) => $b
                ->where('nombre', 'like', "%{$busqueda}%")
                ->orWhereHas('programa', fn ($p) => $p->where('nombre', 'like', "%{$busqueda}%"))))
            ->orderByDesc('fecha_inicio')
            ->paginate(config('limites.por_pagina'));
    }

    /**
     * @param  array{estado?: ?string, tipo?: ?string, q?: ?string}  $filtros
     */
    public function grupos(array $filtros): LengthAwarePaginator
    {
        $estado = EstadoPlanificacion::tryFrom((string) ($filtros['estado'] ?? ''));
        $tipo = $filtros['tipo'] ?? null;
        $busqueda = trim((string) ($filtros['q'] ?? ''));

        return Grupo::with(['modulo', 'docente', 'aula', 'cohorte.programa', 'horarios'])
            ->withCount('inscripciones')
            ->when($tipo === 'suelto', fn ($q) => $q->whereNull('cohorte_id'))
            ->when($tipo === 'cohorte', fn ($q) => $q->whereNotNull('cohorte_id'))
            ->when($estado, fn ($q, $e) => $q->where('estado', $e->value))
            ->when($busqueda !== '', fn ($q) => $q->where(fn ($b) => $b
                ->where('nombre', 'like', "%{$busqueda}%")
                ->orWhereHas('modulo', fn ($m) => $m->where('nombre', 'like', "%{$busqueda}%"))))
            ->ordenadoPorPeriodo('desc')
            ->paginate(config('limites.por_pagina'));
    }
}
