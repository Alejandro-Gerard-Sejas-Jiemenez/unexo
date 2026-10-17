<?php

namespace App\Http\Controllers;

use App\Enums\EstadoPlanificacion;
use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Models\Cohorte;
use App\Models\Programa;
use App\Models\User;
use App\Services\PlanificacionCohorteService;
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
            ->withCount(['grupos', 'matriculas'])
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

        return redirect()->route('cohortes.show', $cohorte)->with('ok', 'Cohorte creada. Ahora puedes aperturar sus grupos operativos.');
    }

    public function show(Cohorte $cohorte): View
    {
        $cohorte->load([
            'programa.modulos',
            'grupos' => fn ($q) => $q->with(['modulo', 'docente', 'aula'])->withCount('inscripciones')->orderBy('fecha_inicio'),
        ]);

        $modulosPrograma = $cohorte->programa->modulos;
        $modulosConGrupoIds = $cohorte->grupos->pluck('modulo_id')->all();
        $modulosPendientes = $modulosPrograma->reject(fn ($m) => in_array($m->id, $modulosConGrupoIds))->values();
        $totalMatriculados = $cohorte->matriculas()->count();
        $docentes = User::where('rol', Rol::Docente->value)
            ->where('estado', 'activo')
            ->withCount(['gruposComoDocente as grupos_activos_count' => fn ($q) => $q->whereIn('estado', [
                EstadoPlanificacion::Programado->value,
                EstadoPlanificacion::EnCurso->value,
            ])])
            ->orderBy('apellidos')
            ->get(['id', 'nombres', 'apellidos']);

        return view('cohortes.show', compact('cohorte', 'modulosPrograma', 'modulosPendientes', 'totalMatriculados', 'docentes'));
    }

    public function aperturarGrupos(Request $request, Cohorte $cohorte, PlanificacionCohorteService $servicio): RedirectResponse
    {
        $datos = $request->validate([
            'docente_id' => ['nullable', 'uuid', Rule::exists('users', 'id')->where('rol', Rol::Docente->value)],
            'modalidad' => ['nullable', Rule::enum(Modalidad::class)],
            'cupo_minimo' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $docente = isset($datos['docente_id']) ? User::find($datos['docente_id']) : null;
        $cupoMinimo = $datos['cupo_minimo'] ?? 20;
        $modalidad = isset($datos['modalidad']) ? Modalidad::from($datos['modalidad']) : Modalidad::Virtual;

        $creados = $servicio->generarGruposParaCohorte($cohorte, $docente, $cupoMinimo, $modalidad);

        if ($creados->isEmpty()) {
            return redirect()->route('cohortes.show', $cohorte)
                ->with('info', 'Todos los módulos de este programa ya tienen grupos aperturados en esta cohorte.');
        }

        return redirect()->route('cohortes.show', $cohorte)
            ->with('ok', "Apertura completada: Se generaron {$creados->count()} grupos operativos secuenciales para la cohorte.");
    }

    public function cambiarEstado(Request $request, Cohorte $cohorte, PlanificacionCohorteService $servicio): RedirectResponse
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::enum(EstadoPlanificacion::class)],
            'propagar_a_grupos' => ['nullable', 'boolean'],
        ]);

        $nuevo = EstadoPlanificacion::from($datos['estado']);
        $servicio->cambiarEstadoCohorte($cohorte, $nuevo, (bool) ($datos['propagar_a_grupos'] ?? false));

        return redirect()->route('cohortes.show', $cohorte)
            ->with('ok', 'Estado de la cohorte actualizado a '.$nuevo->label().'.');
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
