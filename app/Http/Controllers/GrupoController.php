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
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
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
            ->orderByDesc('fecha_inicio')
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

        return view('grupos.form', $this->opciones($grupo, $cohorteId));
    }

    public function store(Request $request): RedirectResponse
    {
        $grupo = Grupo::create($this->validar($request));

        if ($grupo->cohorte_id) {
            return redirect()->route('cohortes.show', $grupo->cohorte_id)->with('ok', 'Grupo aperturado correctamente en la cohorte.');
        }

        return redirect()->route('grupos.index')->with('ok', 'Grupo creado correctamente.');
    }

    public function show(Grupo $grupo): View
    {
        $grupo->load(['modulo', 'docente', 'aula', 'cohorte.programa', 'inscripciones.matricula.estudiante']);

        return view('grupos.show', compact('grupo'));
    }

    public function edit(Grupo $grupo): View
    {
        return view('grupos.form', $this->opciones($grupo, $grupo->cohorte_id));
    }

    public function update(Request $request, Grupo $grupo): RedirectResponse
    {
        $grupo->update($this->validar($request));

        if ($grupo->cohorte_id) {
            return redirect()->route('cohortes.show', $grupo->cohorte_id)->with('ok', 'Grupo actualizado correctamente.');
        }

        return redirect()->route('grupos.index')->with('ok', 'Grupo actualizado correctamente.');
    }

    private function validar(Request $request): array
    {
        $datos = $request->validate([
            'modulo_id' => ['required', 'uuid', Rule::exists('modulos', 'id')],
            'cohorte_id' => ['nullable', 'uuid', Rule::exists('cohortes', 'id')],
            'docente_id' => ['required', 'uuid', Rule::exists('users', 'id')->where('rol', Rol::Docente->value)],
            'aula_id' => ['nullable', 'uuid', Rule::exists('aulas', 'id')],
            'nombre' => ['required', 'string', 'max:'.config('limites.grupos.nombre')],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'modalidad' => ['required', Rule::enum(Modalidad::class)],
            'enlace_videoconferencia' => ['nullable', 'url', 'max:'.config('limites.grupos.enlace_videoconferencia')],
            'cupo_minimo' => ['nullable', 'integer', 'min:0', 'max:'.config('limites.grupos.cupo_minimo')],
            'estado' => ['required', Rule::enum(EstadoPlanificacion::class)],
        ]);

        if (empty($datos['cupo_minimo']) || (int) $datos['cupo_minimo'] <= 0) {
            $datos['cupo_minimo'] = null;
        }

        if (! empty($datos['cohorte_id'])) {
            $cohorte = Cohorte::with('programa.modulos')->find($datos['cohorte_id']);
            if ($cohorte) {
                $moduloPertenece = $cohorte->programa->modulos()->where('modulos.id', $datos['modulo_id'])->exists();
                if (! $moduloPertenece) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'modulo_id' => 'El módulo seleccionado no pertenece al programa de esta cohorte ('.$cohorte->programa->nombre.').',
                    ]);
                }

                $fechaInicioCohorte = Carbon::parse($cohorte->fecha_inicio);
                $fechaFinCohorte = Carbon::parse($cohorte->fecha_fin);

                if ($datos['fecha_inicio'] < $fechaInicioCohorte->format('Y-m-d')) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'fecha_inicio' => 'La fecha de inicio del grupo no puede ser anterior al inicio de la cohorte ('.$fechaInicioCohorte->format('d/m/Y').').',
                    ]);
                }

                if ($datos['fecha_fin'] > $fechaFinCohorte->format('Y-m-d')) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'fecha_fin' => 'La fecha de fin del grupo no puede ser posterior al fin de la cohorte ('.$fechaFinCohorte->format('d/m/Y').').',
                    ]);
                }
            }
        }

        // Reglas de coherencia logística (CU5: Virtual vs Presencial)
        if ($datos['modalidad'] === Modalidad::Virtual->value) {
            $datos['aula_id'] = null;
        } elseif ($datos['modalidad'] === Modalidad::Presencial->value) {
            $datos['enlace_videoconferencia'] = null;

            if (empty($datos['aula_id'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'aula_id' => 'Debe asignar un aula física para grupos en modalidad presencial.',
                ]);
            }

            $aula = Aula::find($datos['aula_id']);
            if ($aula && $aula->capacidad && ! empty($datos['cupo_minimo']) && (int) $datos['cupo_minimo'] > 0 && $aula->capacidad < (int) $datos['cupo_minimo']) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'aula_id' => "La capacidad del aula ({$aula->capacidad} personas) es inferior al cupo mínimo configurado ({$datos['cupo_minimo']}).",
                ]);
            }
        }

        return $datos;
    }

    private function opciones(Grupo $grupo, ?string $cohorteId = null): array
    {
        $cohorteSeleccionada = null;
        if ($cohorteId) {
            $cohorteSeleccionada = Cohorte::with('programa.modulos')->find($cohorteId);
        }

        if ($cohorteSeleccionada) {
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
            'docentes', 'docentesTotal', 'aulas', 'aulasTotal', 'cohorteSeleccionada'
        );
    }
}
