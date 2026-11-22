<?php

namespace App\Http\Controllers\Api\Panel;

use App\Enums\DiaSemana;
use App\Enums\EstadoPlanificacion;
use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Requests\CohorteRequest;
use App\Http\Requests\StoreGrupoRequest;
use App\Http\Requests\UpdateGrupoRequest;
use App\Http\Resources\CohortePanelResource;
use App\Http\Resources\GrupoPanelResource;
use App\Http\Resources\ModuloPanelResource;
use App\Models\Aula;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\User;
use App\Services\GrupoService;
use App\Services\PlanificacionCohorteService;
use App\Services\PlanificacionConsultaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * CU4 (planificar ediciones y aperturar grupos) y CU5 (configuración logística
 * de las clases) por la API del panel.
 */
class PlanificacionApiController extends Controller
{
    public function __construct(
        private readonly PlanificacionConsultaService $consulta,
        private readonly GrupoService $grupos,
    ) {}

    // ------------------------------------------------- CU4: ediciones

    public function cohortes(Request $request): AnonymousResourceCollection
    {
        return CohortePanelResource::collection(
            $this->consulta->cohortes($request->only(['estado', 'q'])),
        );
    }

    public function cohorte(Cohorte $cohorte): JsonResponse
    {
        $cohorte->load([
            'programa.modulos',
            'grupos' => fn ($q) => $q->with(['modulo', 'docente', 'aula', 'horarios'])->withCount('inscripciones'),
        ]);

        $conGrupo = $cohorte->grupos->pluck('modulo_id')->all();

        return response()->json([
            'data' => new CohortePanelResource($cohorte),
            // Módulos del plan que todavía no tienen grupo en esta edición.
            'modulos_pendientes' => ModuloPanelResource::collection(
                $cohorte->programa->modulos->reject(fn ($m) => in_array($m->id, $conGrupo, true))->values(),
            ),
        ]);
    }

    public function guardarCohorte(CohorteRequest $request): JsonResponse
    {
        $cohorte = Cohorte::create($request->datosParaGuardar());

        return response()->json([
            'cohorte' => new CohortePanelResource($cohorte->load('programa')),
            'mensaje' => 'Edición creada. Ahora podés aperturar sus grupos operativos.',
        ], 201);
    }

    public function actualizarCohorte(CohorteRequest $request, Cohorte $cohorte): JsonResponse
    {
        $cohorte->update($request->datosParaGuardar());

        return response()->json([
            'cohorte' => new CohortePanelResource($cohorte->fresh()->load('programa')),
            'mensaje' => 'Edición actualizada correctamente.',
        ]);
    }

    /** Genera un grupo por cada módulo del plan que todavía no lo tenga. */
    public function aperturarGrupos(
        Request $request,
        Cohorte $cohorte,
        PlanificacionCohorteService $planificacion,
    ): JsonResponse {
        $datos = $request->validate([
            'docente_id' => ['nullable', 'uuid', Rule::exists('users', 'id')->where('rol', Rol::Docente->value)],
            'modalidad' => ['nullable', Rule::enum(Modalidad::class)],
            'cupo_minimo' => ['nullable', 'integer', 'min:0', 'max:500'],
        ], [], ['docente_id' => 'docente', 'cupo_minimo' => 'cupo mínimo']);

        $creados = $planificacion->generarGruposParaCohorte(
            $cohorte,
            isset($datos['docente_id']) ? User::find($datos['docente_id']) : null,
            ! empty($datos['cupo_minimo']) ? (int) $datos['cupo_minimo'] : null,
            isset($datos['modalidad']) ? Modalidad::from($datos['modalidad']) : Modalidad::Virtual,
        );

        return response()->json([
            'creados' => $creados->count(),
            'mensaje' => $creados->isEmpty()
                ? 'Todos los módulos de este programa ya tienen grupos en esta edición.'
                : "Se generaron {$creados->count()} grupos operativos para la edición.",
        ]);
    }

    public function cambiarEstadoCohorte(
        Request $request,
        Cohorte $cohorte,
        PlanificacionCohorteService $planificacion,
    ): JsonResponse {
        $datos = $request->validate([
            'estado' => ['required', Rule::enum(EstadoPlanificacion::class)],
            'propagar_a_grupos' => ['nullable', 'boolean'],
        ]);

        $nuevo = EstadoPlanificacion::from($datos['estado']);
        $planificacion->cambiarEstadoCohorte($cohorte, $nuevo, (bool) ($datos['propagar_a_grupos'] ?? false));

        return response()->json([
            'cohorte' => new CohortePanelResource($cohorte->fresh()->load('programa')),
            'mensaje' => 'Estado de la edición actualizado a '.$nuevo->label().'.',
        ]);
    }

    // ------------------------------------------------- CU5: grupos

    public function gruposIndex(Request $request): AnonymousResourceCollection
    {
        return GrupoPanelResource::collection(
            $this->consulta->grupos($request->only(['estado', 'tipo', 'q'])),
        );
    }

    public function grupo(Grupo $grupo): GrupoPanelResource
    {
        return new GrupoPanelResource(
            $grupo->load(['modulo', 'docente', 'aula', 'cohorte.programa', 'horarios'])
                ->loadCount('inscripciones'),
        );
    }

    public function guardarGrupo(StoreGrupoRequest $request): JsonResponse
    {
        $grupo = $this->grupos->crear($request->validated());

        return response()->json([
            'grupo' => new GrupoPanelResource($grupo->load(['modulo', 'docente', 'aula', 'horarios'])),
            'mensaje' => 'Grupo creado correctamente.',
        ], 201);
    }

    public function actualizarGrupo(UpdateGrupoRequest $request, Grupo $grupo): JsonResponse
    {
        $actualizado = $this->grupos->actualizar($grupo, $request->validated());

        return response()->json([
            'grupo' => new GrupoPanelResource($actualizado->load(['modulo', 'docente', 'aula', 'horarios'])),
            'mensaje' => 'Grupo actualizado correctamente.',
        ]);
    }

    /** Catálogos para los selects de los formularios de planificación. */
    public function opciones(): JsonResponse
    {
        return response()->json([
            'programas' => Programa::orderBy('nombre')->get(['id', 'nombre', 'version']),
            'modulos' => ModuloPanelResource::collection(
                Modulo::orderBy('nombre')->limit(config('limites.opciones_select'))->get(),
            ),
            'docentes' => User::where('rol', Rol::Docente->value)
                ->where('estado', 'activo')
                ->orderBy('apellidos')
                ->get(['id', 'nombres', 'apellidos'])
                ->map(fn (User $d) => ['id' => $d->id, 'nombre' => $d->nombre_completo]),
            'aulas' => Aula::orderBy('nombre')->get(['id', 'nombre', 'capacidad']),
            'cohortes' => Cohorte::orderByDesc('fecha_inicio')
                ->limit(config('limites.opciones_select'))
                ->get(['id', 'nombre']),
            'estados' => array_map(
                fn (EstadoPlanificacion $e) => ['valor' => $e->value, 'etiqueta' => $e->label()],
                EstadoPlanificacion::cases(),
            ),
            'modalidades' => array_map(
                fn (Modalidad $m) => ['valor' => $m->value, 'etiqueta' => ucfirst($m->value)],
                Modalidad::cases(),
            ),
            'dias' => array_map(
                fn (DiaSemana $d) => ['valor' => $d->value, 'etiqueta' => $d->label()],
                DiaSemana::cases(),
            ),
            'limites' => config('limites.grupos'),
        ]);
    }
}
