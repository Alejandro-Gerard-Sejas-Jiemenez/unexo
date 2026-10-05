<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AulaController extends Controller
{
    public function index(): View
    {
        return view('aulas.index', [
            'aulas' => Aula::withCount('grupos')->orderBy('nombre')->paginate(config('limites.por_pagina'))->withQueryString(),
            'aula' => new Aula(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Aula::create($this->validar($request, null));

        return redirect()->route('aulas.index')->with('ok', 'Aula registrada correctamente.');
    }

    public function edit(Aula $aula): View
    {
        return view('aulas.edit', compact('aula'));
    }

    public function update(Request $request, Aula $aula): RedirectResponse
    {
        $aula->update($this->validar($request, $aula));

        return redirect()->route('aulas.index')->with('ok', 'Aula actualizada correctamente.');
    }

    private function validar(Request $request, ?Aula $aula): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:'.config('limites.aulas.nombre'), Rule::unique('aulas', 'nombre')->ignore($aula)],
            'capacidad' => ['nullable', 'integer', 'min:1', 'max:'.config('limites.aulas.capacidad')],
        ]);
    }
}
