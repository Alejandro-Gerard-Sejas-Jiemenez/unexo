<?php

namespace App\Http\Controllers;

use App\Enums\EstadoComercial;
use App\Models\Modulo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ModuloController extends Controller
{
    public function index(Request $request): View
    {
        $estado = $request->query('estado');
        $busqueda = trim((string) $request->query('q', ''));

        $modulos = Modulo::withCount('programas')
            ->when(EstadoComercial::tryFrom((string) $estado), fn ($q, $e) => $q->where('estado_comercial', $e->value))
            ->when($busqueda !== '', fn ($q) => $q->where(fn ($b) => $b
                ->where('codigo', 'like', "%{$busqueda}%")
                ->orWhere('nombre', 'like', "%{$busqueda}%")))
            ->orderBy('nombre')
            ->paginate(config('limites.por_pagina'))->withQueryString();

        return view('modulos.index', ['modulos' => $modulos, 'estadoFiltro' => $estado, 'busqueda' => $busqueda]);
    }

    public function create(): View
    {
        return view('modulos.form', ['modulo' => new Modulo(['estado_comercial' => EstadoComercial::Borrador])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Modulo::create($this->datos($request, null));

        return redirect()->route('modulos.index')->with('ok', 'Módulo creado correctamente.');
    }

    public function edit(Modulo $modulo): View
    {
        return view('modulos.form', compact('modulo'));
    }

    public function update(Request $request, Modulo $modulo): RedirectResponse
    {
        $modulo->update($this->datos($request, $modulo));

        return redirect()->route('modulos.index')->with('ok', 'Módulo actualizado.');
    }

    private function datos(Request $request, ?Modulo $modulo): array
    {
        return $request->validate([
            'codigo' => ['required', 'string', 'max:'.config('limites.modulos.codigo'), Rule::unique('modulos', 'codigo')->ignore($modulo)],
            'nombre' => ['required', 'string', 'max:'.config('limites.modulos.nombre')],
            'duracion_horas' => ['nullable', 'integer', 'min:1', 'max:'.config('limites.modulos.duracion_horas')],
            'estado_comercial' => ['required', Rule::enum(EstadoComercial::class)],
        ]);
    }
}
