<?php

namespace App\Http\Controllers;

use App\Enums\EstadoComercial;
use App\Http\Requests\StoreModuloRequest;
use App\Http\Requests\UpdateModuloRequest;
use App\Models\Modulo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function store(StoreModuloRequest $request): RedirectResponse
    {
        Modulo::create($request->validated());

        return redirect()->route('modulos.index')->with('ok', 'Módulo creado correctamente.');
    }

    public function edit(Modulo $modulo): View
    {
        return view('modulos.form', compact('modulo'));
    }

    public function update(UpdateModuloRequest $request, Modulo $modulo): RedirectResponse
    {
        $modulo->update($request->validated());

        return redirect()->route('modulos.index')->with('ok', 'Módulo actualizado.');
    }
}
