<?php

namespace App\Http\Controllers;

use App\Enums\EstadoPlanificacion;
use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Http\Requests\StoreGrupoRequest;
use App\Http\Requests\UpdateGrupoRequest;
use App\Models\Aula;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Modulo;
use App\Models\User;
use App\Services\GrupoService;
use App\Support\Opciones;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GrupoController extends Controller
{
    public function index(Request $request): View
    {
        $estado = $request->query('estado');
        $tipo = $request->query('tipo');
        $busqueda = trim((string) $request->query('q', ''));

        $grupos = Grupo::with(['modulo', 'docente', 'cohorte.programa'])
            ->withCount('inscripciones')
            ->when($tipo === 'suelto', fn ($q) => $q->whereNull('cohorte_id'))
            ->when($tipo === 'cohorte', fn ($q) => $q->whereNotNull('cohorte_id'))
            ->when(EstadoPlanificacion::tryFrom((string) $estado), fn ($q, $e) => $q->where('estado', $e->value))
            ->when($busqueda !== '', fn ($q) => $q->where(fn ($b) => $b
                ->where('nombre', 'like', "%{$busqueda}%")
                ->orWhereHas('modulo', fn ($m) => $m->where('nombre', 'like', "%{$busqueda}%"))))
            ->ordenadoPorPeriodo('desc')
            ->paginate(config('limites.por_pagina'))->withQueryString();

        return view('grupos.index', [
            'grupos' => $grupos,
            'estadoFiltro' => $estado,
            'tipoFiltro' => $tipo,
            'busqueda' => $busqueda,
        ]);
    }

    public function create(Request $request): View
    {
        $cohorteId = $request->query('cohorte_id');

        $grupo = new Grupo([
            'estado' => EstadoPlanificacion::Programado,
            'modalidad' => Modalidad::Virtual,
            'cohorte_id' => $cohorteId,
            'cupo_minimo' => null,
        ]);

        return view('grupos.form', $this->opciones($grupo, $cohorteId, (bool) $cohorteId));
    }

    public function store(StoreGrupoRequest $request): RedirectResponse
    {
        $grupo = app(GrupoService::class)->crear($request->validated());

        if ($grupo->cohorte_id) {
            return redirect()->route('cohortes.show', $grupo->cohorte_id)->with('ok', 'Grupo aperturado correctamente en la cohorte.');
        }

        return redirect()->route('grupos.index')->with('ok', 'Grupo creado correctamente.');
    }

    public function show(Grupo $grupo): View
    {
        $grupo->load(['modulo', 'docente', 'aula', 'cohorte.programa', 'horarios', 'inscripciones.matricula.estudiante']);

        return view('grupos.show', compact('grupo'));
    }

    public function edit(Grupo $grupo): View
    {
        $grupo->load('horarios');

        return view('grupos.form', $this->opciones($grupo, $grupo->cohorte_id, false));
    }

    public function update(UpdateGrupoRequest $request, Grupo $grupo): RedirectResponse
    {
        app(GrupoService::class)->actualizar($grupo, $request->validated());

        if ($grupo->cohorte_id) {
            return redirect()->route('cohortes.show', $grupo->cohorte_id)->with('ok', 'Grupo actualizado correctamente.');
        }

        return redirect()->route('grupos.index')->with('ok', 'Grupo actualizado correctamente.');
    }

    /** @param  bool  $edicionFija  Alta desde una edición: los módulos se limitan a su programa y la edición no se cambia. */
    private function opciones(Grupo $grupo, ?string $cohorteId, bool $edicionFija): array
    {
        $cohorteSeleccionada = $cohorteId ? Cohorte::with('programa.modulos')->find($cohorteId) : null;

        if ($cohorteSeleccionada && $edicionFija) {
            $modulos = $cohorteSeleccionada->programa->modulos;
            $modulosTotal = $modulos->count();
        } else {
            [$modulos, $modulosTotal] = Opciones::limitar(Modulo::query()->orderBy('nombre'), ['id', 'codigo', 'nombre']);
        }

        [$cohortes, $cohortesTotal] = Opciones::limitar(Cohorte::query()->with('programa')->orderByDesc('fecha_inicio'));
        [$docentes, $docentesTotal] = Opciones::limitar(
            User::query()
                ->where('rol', Rol::Docente->value)
                ->where('estado', 'activo')
                ->withCount(['gruposComoDocente as grupos_activos_count' => fn ($q) => $q->whereIn('estado', [
                    EstadoPlanificacion::Programado->value,
                    EstadoPlanificacion::EnCurso->value,
                ])])
                ->orderBy('apellidos'),
            ['id', 'nombres', 'apellidos']
        );
        [$aulas, $aulasTotal] = Opciones::limitar(Aula::query()->orderBy('nombre'), ['id', 'nombre', 'capacidad']);

        return compact(
            'grupo', 'modulos', 'modulosTotal', 'cohortes', 'cohortesTotal',
            'docentes', 'docentesTotal', 'aulas', 'aulasTotal', 'cohorteSeleccionada', 'edicionFija'
        );
    }
}
