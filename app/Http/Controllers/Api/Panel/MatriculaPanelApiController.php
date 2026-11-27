<?php

namespace App\Http\Controllers\Api\Panel;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use App\Enums\EstadoMatricula;
use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Requests\EstadoMatriculaRequest;
use App\Http\Requests\MatriculaStoreApiRequest;
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
use Illuminate\Validation\ValidationException;

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
            'inscripciones.grupo.cohorte',
            'comprobantes.subidoPor',
        ]));
    }

    public function store(MatriculaStoreApiRequest $request): JsonResponse
    {
        $datos = $request->validated();
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
            'matricula' => new MatriculaPanelResource($matricula->load(['estudiante', 'cohorte.programa'])),
            'mensaje' => 'Matrícula registrada. Queda pendiente de pago.',
        ], 201);
    }

    public function cambiarEstado(EstadoMatriculaRequest $request, Matricula $matricula): JsonResponse
    {
        $nuevo = EstadoMatricula::from($request->validated('estado'));
        $this->matriculas->cambiarEstado($matricula, $nuevo);

        return response()->json([
            'matricula' => new MatriculaPanelResource($matricula->fresh()->load(['estudiante', 'cohorte.programa'])),
            'mensaje' => 'Estado de la matrícula actualizado a '.$nuevo->label().'.',
        ]);
    }

    public function opciones(Request $request): JsonResponse
    {
        $limite = (int) config('limites.opciones_select');

        return response()->json([
            'estudiantes' => $this->estudiantesParaElegir($limite, (string) $request->query('estudiante_id', ''))
                ->map(fn (User $e) => [
                    'id' => $e->id,
                    'nombre' => $e->nombre_completo,
                    'email' => $e->email,
                ]),
            'cohortes' => Cohorte::with('programa')
                ->withCount('grupos')
                ->orderByDesc('fecha_inicio')
                ->limit($limite)
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
                ->limit($limite)
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

    /** Los primeros estudiantes activos y, si un atajo pidió uno, ese también. */
    private function estudiantesParaElegir(int $limite, string $pedido): Collection
    {
        $activos = fn () => User::where('rol', Rol::Estudiante->value)->where('estado', 'activo');
        $columnas = ['id', 'nombres', 'apellidos', 'email'];

        $lista = $activos()->orderBy('apellidos')->limit($limite)->get($columnas)->toBase();

        if (Str::isUuid($pedido) && ! $lista->contains('id', $pedido)) {
            $lista = $lista->concat($activos()->whereKey($pedido)->get($columnas));
        }

        return $lista;
    }
}
