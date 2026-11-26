<?php

namespace App\Services;

use App\Support\TamanoPagina;
use App\Enums\EstadoComercial;
use App\Models\Modulo;
use App\Models\Programa;
use App\Support\ConsultaTexto;
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
    public function programas(array $filtros, bool $soloVisibles = false): LengthAwarePaginator
    {
        $estado = EstadoComercial::tryFrom((string) ($filtros['estado'] ?? ''));
        $sinModulosAbiertos = ($filtros['atencion'] ?? null) === 'sin-modulos-abiertos';
        $busqueda = trim((string) ($filtros['q'] ?? ''));

        return Programa::withCount('modulos')
            ->when($soloVisibles, fn ($q) => $q->visibleParaCrm())
            ->when(! $soloVisibles && $estado !== null, fn ($q) => $q->where('estado_comercial', $estado->value))
            ->when(! $soloVisibles && $sinModulosAbiertos, fn ($q) => $q->abiertoSinModulosAbiertos())
            ->when($busqueda !== '', fn ($q) => $q->where(function ($b) use ($busqueda) {
                ConsultaTexto::dondeContiene($b, 'codigo', $busqueda);
                ConsultaTexto::dondeContiene($b, 'nombre', $busqueda, true);
            }))
            ->orderBy('nombre')
            ->paginate(TamanoPagina::elegido());
    }

    /**
     * @param  array{estado?: ?string, q?: ?string}  $filtros
     */
    public function modulos(array $filtros, bool $soloVisibles = false): LengthAwarePaginator
    {
        $estado = EstadoComercial::tryFrom((string) ($filtros['estado'] ?? ''));
        $busqueda = trim((string) ($filtros['q'] ?? ''));

        return Modulo::withCount('programas')
            ->when($soloVisibles, fn ($q) => $q->visibleParaCrm())
            ->when(! $soloVisibles && $estado !== null, fn ($q) => $q->where('estado_comercial', $estado->value))
            ->when($busqueda !== '', fn ($q) => $q->where(function ($b) use ($busqueda) {
                ConsultaTexto::dondeContiene($b, 'codigo', $busqueda);
                ConsultaTexto::dondeContiene($b, 'nombre', $busqueda, true);
            }))
            ->orderBy('nombre')
            ->paginate(TamanoPagina::elegido());
    }
}
