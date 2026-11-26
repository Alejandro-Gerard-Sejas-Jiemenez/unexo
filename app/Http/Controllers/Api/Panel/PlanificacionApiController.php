<?php

namespace App\Http\Controllers\Api\Panel;

use App\Enums\DiaSemana;
use App\Enums\EstadoMatricula;
use App\Enums\EstadoPlanificacion;
use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Requests\AperturarGruposRequest;
use App\Http\Requests\CohorteRequest;
use App\Http\Requests\DocenteGrupoRequest;
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

class PlanificacionApiController extends Controller
{
    public function __construct(
        private readonly PlanificacionConsultaService $consulta,
        private readonly GrupoService $grupos,
    ) {}

    public function cohortes(Request $request): AnonymousResourceCollection
    {
        return CohortePanelResource::collection(
            $this->consulta->cohortes($request->only(['estado', 'q'])),
        );
    }

    public function cohorte(Cohorte $cohorte): JsonResponse
    {
        $estadosInscritos = array_map(fn (EstadoMatricula $e) => $e->value, EstadoMatricula::queCuentanComoInscritos());

        $cohorte->loadCount('matriculas')->load([
            'programa.modulos',
            'grupos' => fn ($q) => $q->with(['modulo', 'docente', 'aula', 'horarios', 'cohorte.programa'])
                ->withCount(['inscripciones as inscritos_count' => fn ($c) => $c->whereHas(
                    'matricula',
                    fn ($m) => $m->whereIn('estado', $estadosInscritos),
                )]),
        ]);

        $ordenModulos = $cohorte->programa->modulos->pluck('pivot.orden', 'id');
        $gruposOrdenados = $cohorte->grupos
            ->sortBy(fn (Grupo $g) => [$ordenModulos[$g->modulo_id] ?? PHP_INT_MAX, $g->numero ?? PHP_INT_MAX, $g->nombre])
            ->values();
        $cohorte->setRelation('grupos', $gruposOrdenados);

        $conGrupo = $cohorte->grupos->pluck('modulo_id')->all();

        return response()->json([
            'data' => new CohortePanelResource($cohorte),
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
            'mensaje' => 'Edición creada. Ahora puedes aperturar sus grupos operativos.',
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

    public function aperturarGrupos(
        AperturarGruposRequest $request,
        Cohorte $cohorte,
        PlanificacionCohorteService $planificacion,
    ): JsonResponse {
        $datos = $request->validated();

        $creados = $planificacion->generarGruposParaCohorte(
            $cohorte,
            isset($datos['docente_id']) ? User::find($datos['docente_id']) : null,
            ! empty($datos['cupo_minimo']) ? (int) $datos['cupo_minimo'] : null,
            isset($datos['modalidad']) ? Modalidad::from($datos['modalidad']) : Modalidad::Virtual,
            $datos['docentes'] ?? null,
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

    public function gruposIndex(Request $request): AnonymousResourceCollection
    {
        return GrupoPanelResource::collection(
            $this->consulta->grupos($request->only(['estado', 'tipo', 'q'])),
        );
    }

    public function grupo(Grupo $grupo): GrupoPanelResource
    {
        $estadosInscritos = array_map(fn (EstadoMatricula $e) => $e->value, EstadoMatricula::queCuentanComoInscritos());

        return new GrupoPanelResource(
            $grupo->load([
                'modulo',
                'docente',
                'aula',
                'cohorte.programa',
                'horarios',
                'inscripciones.matricula.estudiante',
            ])->loadCount([
                'inscripciones as inscritos_count' => fn ($q) => $q->whereHas(
                    'matricula',
                    fn ($m) => $m->whereIn('estado', $estadosInscritos),
                ),
            ]),
        );
    }

    public function guardarGrupo(StoreGrupoRequest $request): JsonResponse
    {
        $grupo = $this->grupos->crear($request->validated());

        return response()->json([
            'grupo' => new GrupoPanelResource($grupo->load(['modulo', 'docente', 'aula', 'cohorte.programa', 'horarios'])),
            'mensaje' => 'Grupo creado correctamente.',
        ], 201);
    }

    public function actualizarGrupo(UpdateGrupoRequest $request, Grupo $grupo): JsonResponse
    {
        $actualizado = $this->grupos->actualizar($grupo, $request->validated());

        return response()->json([
            'grupo' => new GrupoPanelResource($actualizado->load(['modulo', 'docente', 'aula', 'cohorte.programa', 'horarios'])),
            'mensaje' => 'Grupo actualizado correctamente.',
        ]);
    }

    public function cambiarDocenteGrupo(DocenteGrupoRequest $request, Grupo $grupo): JsonResponse
    {
        $this->grupos->cambiarDocente($grupo, $request->validated('docente_id'));

        return response()->json(['mensaje' => 'Docente actualizado.']);
    }

    public function opciones(): JsonResponse
    {
        $limite = (int) config('limites.opciones_select');
        $activos = [EstadoPlanificacion::Programado->value, EstadoPlanificacion::EnCurso->value];

        return response()->json([
            'programas' => Programa::orderBy('nombre')->limit($limite)->get(['id', 'nombre', 'version']),
            'modulos' => ModuloPanelResource::collection(
                Modulo::orderBy('nombre')->limit($limite)->get(),
            ),
            'docentes' => User::where('rol', Rol::Docente->value)
                ->where('estado', 'activo')
                ->withCount(['gruposComoDocente as grupos_activos_count' => fn ($q) => $q->whereIn('estado', $activos)])
                ->orderBy('apellidos')
                ->limit($limite)
                ->get(['id', 'nombres', 'apellidos'])
                ->map(fn (User $d) => [
                    'id' => $d->id,
                    'nombre' => $d->nombre_completo,
                    'grupos_activos_count' => (int) $d->grupos_activos_count,
                ]),
            'aulas' => Aula::orderBy('nombre')->limit($limite)->get(['id', 'nombre', 'capacidad']),
            'cohortes' => Cohorte::with(['programa:id,nombre', 'programa.modulos:modulos.id'])
                ->orderByDesc('fecha_inicio')
                ->limit($limite)
                ->get(['id', 'nombre', 'programa_id'])
                ->map(fn (Cohorte $c) => [
                    'id' => $c->id,
                    'nombre' => $c->nombre,
                    'programa_id' => $c->programa_id,
                    'programa' => $c->programa?->nombre,
                    'modulo_ids' => $c->programa?->modulos?->pluck('id')->values()->all() ?? [],
                ]),
            'estados' => array_map(
                fn (EstadoPlanificacion $e) => ['valor' => $e->value, 'etiqueta' => $e->label()],
                EstadoPlanificacion::cases(),
            ),
            'modalidades' => array_map(
                fn (Modalidad $m) => ['valor' => $m->value, 'etiqueta' => $m->label()],
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
