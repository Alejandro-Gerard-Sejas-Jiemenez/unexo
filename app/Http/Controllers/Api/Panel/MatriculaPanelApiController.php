<?php

namespace App\Http\Controllers\Api\Panel;

use App\Enums\EstadoMatricula;
use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Resources\MatriculaPanelResource;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\User;
use App\Services\MatriculaConsultaService;
use App\Services\MatriculaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * CU6 (recepción y matriculación flexible) y CU7 (estado de la matrícula)
 * por la API del panel.
 */
class MatriculaPanelApiController extends Controller
{
    public function __construct(
        private readonly MatriculaConsultaService $consulta,
        private readonly MatriculaService $matriculas,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        return MatriculaPanelResource::collection(
            $this->consulta->listar($request->only(['estado', 'origen', 'q'])),
        );
    }

    public function show(Matricula $matricula): MatriculaPanelResource
    {
        return new MatriculaPanelResource($matricula->load([
            'estudiante',
            'cohorte.programa',
            'inscripciones.grupo.modulo',
            'inscripciones.grupo.docente',
            'comprobantes.subidoPor',
        ]));
    }

    /**
     * Matriculación flexible: a una edición completa o a un grupo suelto.
     * La transacción y la generación de inscripciones viven en MatriculaService.
     */
    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'estudiante_id' => ['required', 'uuid', Rule::exists('users', 'id')->where('rol', Rol::Estudiante->value)],
            'tipo' => ['required', 'in:cohorte,suelto'],
            'cohorte_id' => ['required_if:tipo,cohorte', 'nullable', 'uuid', Rule::exists('cohortes', 'id')],
            'grupo_id' => ['required_if:tipo,suelto', 'nullable', 'uuid', Rule::exists('grupos', 'id')->whereNull('cohorte_id')],
        ], [], [
            'estudiante_id' => 'estudiante',
            'cohorte_id' => 'edición',
            'grupo_id' => 'grupo',
        ]);

        $estudiante = User::findOrFail($datos['estudiante_id']);

        if ($datos['tipo'] === 'cohorte') {
            $cohorte = Cohorte::findOrFail($datos['cohorte_id']);

            if ($this->matriculas->yaInscritoEnCohorte($estudiante, $cohorte)) {
                throw ValidationException::withMessages([
                    'cohorte_id' => 'El estudiante ya está matriculado en esta edición.',
                ]);
            }

            $matricula = $this->matriculas->inscribirEnCohorte($estudiante, $cohorte);
        } else {
            $grupo = Grupo::whereNull('cohorte_id')->findOrFail($datos['grupo_id']);

            if ($this->matriculas->yaInscritoEnGrupo($estudiante, $grupo)) {
                throw ValidationException::withMessages([
                    'grupo_id' => 'El estudiante ya está inscrito en este grupo.',
                ]);
            }

            $matricula = $this->matriculas->inscribirEnGrupoSuelto($estudiante, $grupo);
        }

        return response()->json([
            'matricula' => new MatriculaPanelResource($matricula->load('estudiante')),
            'mensaje' => 'Matrícula registrada. Queda pendiente de pago.',
        ], 201);
    }

    public function cambiarEstado(Request $request, Matricula $matricula): JsonResponse
    {
        $datos = $request->validate(['estado' => ['required', Rule::enum(EstadoMatricula::class)]]);

        $nuevo = EstadoMatricula::from($datos['estado']);
        $this->matriculas->cambiarEstado($matricula, $nuevo);

        return response()->json([
            'matricula' => new MatriculaPanelResource($matricula->fresh()->load('estudiante')),
            'mensaje' => 'Estado de la matrícula actualizado a '.$nuevo->label().'.',
        ]);
    }

    /** Catálogos para el formulario de matriculación. */
    public function opciones(): JsonResponse
    {
        return response()->json([
            'estudiantes' => User::where('rol', Rol::Estudiante->value)
                ->where('estado', 'activo')
                ->orderBy('apellidos')
                ->limit(config('limites.opciones_select'))
                ->get(['id', 'nombres', 'apellidos', 'email'])
                ->map(fn (User $e) => [
                    'id' => $e->id,
                    'nombre' => $e->nombre_completo,
                    'email' => $e->email,
                ]),
            'cohortes' => Cohorte::with('programa')
                ->withCount('grupos')
                ->orderByDesc('fecha_inicio')
                ->limit(config('limites.opciones_select'))
                ->get()
                ->map(fn (Cohorte $c) => [
                    'id' => $c->id,
                    'nombre' => $c->nombre,
                    'programa' => $c->programa?->nombre,
                    'grupos' => $c->grupos_count,
                ]),
            'grupos_sueltos' => Grupo::whereNull('cohorte_id')
                ->with('modulo')
                ->orderBy('fecha_inicio')
                ->limit(config('limites.opciones_select'))
                ->get()
                ->map(fn (Grupo $g) => [
                    'id' => $g->id,
                    'nombre' => $g->nombre,
                    'modulo' => $g->modulo?->nombre,
                ]),
            'estados' => array_map(
                fn (EstadoMatricula $e) => ['valor' => $e->value, 'etiqueta' => $e->label()],
                EstadoMatricula::cases(),
            ),
        ]);
    }
}
