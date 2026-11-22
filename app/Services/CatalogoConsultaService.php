<?php

namespace App\Services;

use App\Enums\EstadoComercial;
use App\Models\Modulo;
use App\Models\Programa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Listados del catálogo con filtros. Los comparten el panel Blade y la API del
 * panel, para que los dos filtren y ordenen igual.
 */
class CatalogoConsultaService
{
    /**
     * @param  array{estado?: ?string, atencion?: ?string, q?: ?string}  $filtros
     */
    public function programas(array $filtros): LengthAwarePaginator
    {
        $estado = EstadoComercial::tryFrom((string) ($filtros['estado'] ?? ''));
        $sinModulosAbiertos = ($filtros['atencion'] ?? null) === 'sin-modulos-abiertos';
        $busqueda = trim((string) ($filtros['q'] ?? ''));

        return Programa::withCount('modulos')
            ->when($estado, fn ($q, $e) => $q->where('estado_comercial', $e->value))
            ->when($sinModulosAbiertos, fn ($q) => $q->abiertoSinModulosAbiertos())
            ->when($busqueda !== '', fn ($q) => $q->where(fn ($b) => $b
                ->where('codigo', 'like', "%{$busqueda}%")
                ->orWhere('nombre', 'like', "%{$busqueda}%")))
            ->orderBy('nombre')
            ->paginate(config('limites.por_pagina'));
    }

    /**
     * @param  array{estado?: ?string, q?: ?string}  $filtros
     */
    public function modulos(array $filtros): LengthAwarePaginator
    {
        $estado = EstadoComercial::tryFrom((string) ($filtros['estado'] ?? ''));
        $busqueda = trim((string) ($filtros['q'] ?? ''));

        return Modulo::withCount('programas')
            ->when($estado, fn ($q, $e) => $q->where('estado_comercial', $e->value))
            ->when($busqueda !== '', fn ($q) => $q->where(fn ($b) => $b
                ->where('codigo', 'like', "%{$busqueda}%")
                ->orWhere('nombre', 'like', "%{$busqueda}%")))
            ->orderBy('nombre')
            ->paginate(config('limites.por_pagina'));
    }
}
