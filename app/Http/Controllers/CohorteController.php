<?php

namespace App\Http\Controllers;

use App\Enums\EstadoPlanificacion;
use App\Models\Cohorte;
use App\Models\Programa;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CohorteController extends Controller
{
    public function index(Request $request): View
    {
        $estado = $request->query('estado');
        $busqueda = trim((string) $request->query('q', ''));

        $cohortes = Cohorte::with('programa')
            ->withCount('grupos')
            ->when(EstadoPlanificacion::tryFrom((string) $estado), fn ($q, $e) => $q->where('estado', $e->value))
            ->when($busqueda !== '', fn ($q) => $q->where(fn ($b) => $b
                ->where('nombre', 'like', "%{$busqueda}%")
                ->orWhereHas('programa', fn ($p) => $p->where('nombre', 'like', "%{$busqueda}%"))))
            ->orderByDesc('fecha_inicio')
            ->paginate(config('limites.por_pagina'))->withQueryString();

        return view('cohortes.index', ['cohortes' => $cohortes, 'estadoFiltro' => $estado, 'busqueda' => $busqueda]);
    }

    public function create(): View
    {
        return view('cohortes.form', ['cohorte' => new Cohorte(['estado' => EstadoPlanificacion::Programado]), 'programas' => $this->programas()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $cohorte = Cohorte::create($this->validar($request));

        return redirect()->route('cohortes.show', $cohorte)->with('ok', 'Cohorte creada. Ahora agrega sus grupos.');
    }

    public function show(Cohorte $cohorte): View
    {
        $cohorte->load(['programa', 'grupos.modulo', 'grupos.docente']);

        return view('cohortes.show', compact('cohorte'));
    }

    public function edit(Cohorte $cohorte): View
    {
        return view('cohortes.form', ['cohorte' => $cohorte, 'programas' => $this->programas()]);
    }

    public function update(Request $request, Cohorte $cohorte): RedirectResponse
    {
        $cohorte->update($this->validar($request));

        return redirect()->route('cohortes.show', $cohorte)->with('ok', 'Cohorte actualizada correctamente.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'programa_id' => ['required', 'uuid', Rule::exists('programas', 'id')],
            'nombre' => ['required', 'string', 'max:'.config('limites.cohortes.nombre')],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'estado' => ['required', Rule::enum(EstadoPlanificacion::class)],
        ]);
    }

    private function programas()
    {
        return Programa::orderBy('nombre')->get(['id', 'nombre', 'version']);
    }
}
