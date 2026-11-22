<?php

namespace App\Http\Controllers;

use App\Enums\EstadoMatricula;
use App\Enums\Rol;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\User;
use App\Services\MatriculaConsultaService;
use App\Services\MatriculaService;
use App\Support\Opciones;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MatriculaController extends Controller
{
    public function __construct(private readonly MatriculaConsultaService $consulta) {}

    public function index(Request $request): View
    {
        $filtros = $request->only(['estado', 'origen', 'q']);

        return view('matriculas.index', [
            'matriculas' => $this->consulta->listar($filtros)->withQueryString(),
            'estadoFiltro' => $filtros['estado'] ?? null,
            'busqueda' => trim((string) ($filtros['q'] ?? '')),
        ]);
    }

    public function create(): View
    {
        [$estudiantes, $estudiantesTotal] = Opciones::limitar(
            User::where('rol', Rol::Estudiante->value)->orderBy('apellidos'), ['id', 'nombres', 'apellidos']
        );
        [$cohortes, $cohortesTotal] = Opciones::limitar(
            Cohorte::with('programa')->withCount('grupos')->orderByDesc('fecha_inicio')
        );
        [$gruposSueltos, $gruposSueltosTotal] = Opciones::limitar(
            Grupo::whereNull('cohorte_id')->with('modulo')->orderBy('fecha_inicio')
        );

        return view('matriculas.create', compact(
            'estudiantes', 'estudiantesTotal', 'cohortes', 'cohortesTotal', 'gruposSueltos', 'gruposSueltosTotal'
        ));
    }

    public function store(Request $request, MatriculaService $servicio): RedirectResponse
    {
        $datos = $request->validate([
            'estudiante_id' => ['required', 'uuid', Rule::exists('users', 'id')->where('rol', Rol::Estudiante->value)],
            'tipo' => ['required', 'in:cohorte,suelto'],
            'cohorte_id' => ['required_if:tipo,cohorte', 'nullable', 'uuid', Rule::exists('cohortes', 'id')],
            'grupo_id' => ['required_if:tipo,suelto', 'nullable', 'uuid', Rule::exists('grupos', 'id')->whereNull('cohorte_id')],
        ]);

        $estudiante = User::findOrFail($datos['estudiante_id']);

        if ($datos['tipo'] === 'cohorte') {
            $cohorte = Cohorte::findOrFail($datos['cohorte_id']);
            if ($servicio->yaInscritoEnCohorte($estudiante, $cohorte)) {
                throw ValidationException::withMessages(['cohorte_id' => 'El estudiante ya está matriculado en esta cohorte.']);
            }
            $matricula = $servicio->inscribirEnCohorte($estudiante, $cohorte);
        } else {
            $grupo = Grupo::whereNull('cohorte_id')->findOrFail($datos['grupo_id']);
            if ($servicio->yaInscritoEnGrupo($estudiante, $grupo)) {
                throw ValidationException::withMessages(['grupo_id' => 'El estudiante ya está inscrito en este grupo.']);
            }
            $matricula = $servicio->inscribirEnGrupoSuelto($estudiante, $grupo);
        }

        return redirect()->route('matriculas.show', $matricula)->with('ok', 'Matrícula registrada. Queda pendiente de pago.');
    }

    public function show(Matricula $matricula): View
    {
        $matricula->load(['estudiante', 'cohorte.programa', 'inscripciones.grupo.modulo', 'inscripciones.grupo.docente', 'inscripciones.grupo.cohorte', 'comprobantes.subidoPor']);

        return view('matriculas.show', compact('matricula'));
    }

    public function cambiarEstado(Request $request, Matricula $matricula, MatriculaService $servicio): RedirectResponse
    {
        $datos = $request->validate([
            'estado' => ['required', Rule::enum(EstadoMatricula::class)],
        ]);

        $nuevo = EstadoMatricula::from($datos['estado']);

        $servicio->cambiarEstado($matricula, $nuevo);

        return redirect()->route('matriculas.show', $matricula)->with('ok', 'Estado de la matrícula actualizado a '.$nuevo->label().'.');
    }
}
