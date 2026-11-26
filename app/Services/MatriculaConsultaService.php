<?php

namespace App\Services;

use App\Enums\EstadoMatricula;
use App\Models\Matricula;
use App\Support\ConsultaTexto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Listado de matrículas con filtros. Lo comparten el panel Blade y la API.
 */
class MatriculaConsultaService
{
    /**
     * @param  array{estado?: ?string, origen?: ?string, q?: ?string}  $filtros
     */
    public function listar(array $filtros): LengthAwarePaginator
    {
        $estado = EstadoMatricula::tryFrom((string) ($filtros['estado'] ?? ''));
        $origen = $filtros['origen'] ?? null;
        $busqueda = trim((string) ($filtros['q'] ?? ''));

        return Matricula::with(['estudiante', 'cohorte.programa'])
            ->withCount('inscripciones')
            ->when($estado, fn ($q, $e) => $q->where('estado', $e->value))
            ->when(in_array($origen, ['manual', 'crm'], true), fn ($q) => $q->where('origen', $origen))
            ->when($busqueda !== '', fn ($q) => $q->whereHas('estudiante', function ($u) use ($busqueda) {
                ConsultaTexto::dondeContiene($u, 'nombres', $busqueda);
                ConsultaTexto::dondeContiene($u, 'apellidos', $busqueda, true);
                ConsultaTexto::dondeContiene($u, 'email', $busqueda, true);
            }))
            ->orderByDesc('fecha_matricula')
            ->paginate(config('limites.por_pagina'));
    }
}
