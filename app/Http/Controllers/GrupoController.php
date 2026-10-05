<?php

namespace App\Http\Controllers;

use App\Support\Opciones;
use App\Enums\EstadoPlanificacion;
use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Models\Aula;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Modulo;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GrupoController extends Controller
{
    public function index(Request $request): View
    {
        $estado = $request->query('estado');
        $busqueda = trim((string) $request->query('q', ''));

        $grupos = Grupo::with(['modulo', 'docente', 'cohorte.programa'])
            ->withCount('inscripciones')
            ->when(EstadoPlanificacion::tryFrom((string) $estado), fn ($q, $e) => $q->where('estado', $e->value))
            ->when($busqueda !== '', fn ($q) => $q->where(fn ($b) => $b
                ->where('nombre', 'like', "%{$busqueda}%")
                ->orWhereHas('modulo', fn ($m) => $m->where('nombre', 'like', "%{$busqueda}%"))))
            ->orderByDesc('fecha_inicio')
            ->paginate(config('limites.por_pagina'))->withQueryString();

        return view('grupos.index', ['grupos' => $grupos, 'estadoFiltro' => $estado, 'busqueda' => $busqueda]);
    }

    public function create(Request $request): View
    {
        return view('grupos.form', $this->opciones(new Grupo([
            'estado' => EstadoPlanificacion::Programado,
            'modalidad' => Modalidad::Virtual,
            'cohorte_id' => $request->query('cohorte_id'),
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        Grupo::create($this->validar($request));

        return redirect()->route('grupos.index')->with('ok', 'Grupo creado correctamente.');
    }

    public function show(Grupo $grupo): View
    {
        $grupo->load(['modulo', 'docente', 'aula', 'cohorte.programa', 'inscripciones.matricula.estudiante']);

        return view('grupos.show', compact('grupo'));
    }

    public function edit(Grupo $grupo): View
    {
        return view('grupos.form', $this->opciones($grupo));
    }

    public function update(Request $request, Grupo $grupo): RedirectResponse
    {
        $grupo->update($this->validar($request));

        return redirect()->route('grupos.index')->with('ok', 'Grupo actualizado correctamente.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'modulo_id' => ['required', 'uuid', Rule::exists('modulos', 'id')],
            'cohorte_id' => ['nullable', 'uuid', Rule::exists('cohortes', 'id')],
            'docente_id' => ['required', 'uuid', Rule::exists('users', 'id')->where('rol', Rol::Docente->value)],
            'aula_id' => ['nullable', 'uuid', Rule::exists('aulas', 'id')],
            'nombre' => ['required', 'string', 'max:'.config('limites.grupos.nombre')],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'modalidad' => ['required', Rule::enum(Modalidad::class)],
            'enlace_videoconferencia' => ['nullable', 'url', 'max:'.config('limites.grupos.enlace_videoconferencia')],
            'cupo_minimo' => ['nullable', 'integer', 'min:1', 'max:'.config('limites.grupos.cupo_minimo')],
            'estado' => ['required', Rule::enum(EstadoPlanificacion::class)],
        ]);
    }

    private function opciones(Grupo $grupo): array
    {
        [$modulos, $modulosTotal] = Opciones::limitar(Modulo::orderBy('nombre'), ['id', 'codigo', 'nombre']);
        [$cohortes, $cohortesTotal] = Opciones::limitar(Cohorte::with('programa')->orderByDesc('fecha_inicio'));
        [$docentes, $docentesTotal] = Opciones::limitar(
            User::where('rol', Rol::Docente->value)->orderBy('apellidos'), ['id', 'nombres', 'apellidos']
        );
        [$aulas, $aulasTotal] = Opciones::limitar(Aula::orderBy('nombre'), ['id', 'nombre']);

        return compact(
            'grupo', 'modulos', 'modulosTotal', 'cohortes', 'cohortesTotal',
            'docentes', 'docentesTotal', 'aulas', 'aulasTotal'
        );
    }
}
