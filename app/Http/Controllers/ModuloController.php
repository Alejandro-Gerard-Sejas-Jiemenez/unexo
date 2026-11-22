<?php

namespace App\Http\Controllers;

use App\Enums\EstadoComercial;
use App\Http\Requests\StoreModuloRequest;
use App\Http\Requests\UpdateModuloRequest;
use App\Models\Modulo;
use App\Services\CatalogoConsultaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuloController extends Controller
{
    public function __construct(private readonly CatalogoConsultaService $catalogo) {}

    public function index(Request $request): View
    {
        $filtros = $request->only(['estado', 'q']);

        return view('modulos.index', [
            'modulos' => $this->catalogo->modulos($filtros)->withQueryString(),
            'estadoFiltro' => $filtros['estado'] ?? null,
            'busqueda' => trim((string) ($filtros['q'] ?? '')),
        ]);
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
