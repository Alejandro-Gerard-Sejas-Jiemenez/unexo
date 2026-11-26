<?php

namespace App\Http\Controllers\Api\Panel;

use App\Enums\EstadoMatricula;
use App\Http\Controllers\Controller;
use App\Http\Resources\GrupoPanelResource;
use App\Models\Grupo;
use App\Models\InscripcionGrupo;
use App\Models\Matricula;
use App\Services\CalendarioService;
use App\Services\MisClasesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MisClasesApiController extends Controller
{
    public function __construct(
        private readonly MisClasesService $clases,
        private readonly CalendarioService $calendario,
    ) {}

    public function misGrupos(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $todos = $this->clases->gruposDelDocente($usuario);
        $grupoDe = fn ($g) => $g;

        $fase = (string) $request->query('fase', '');
        $busqueda = (string) $request->query('q', '');
        $segunBusqueda = $this->clases->filtrar($todos, $grupoDe, null, $busqueda);

        return response()->json([
            'data' => GrupoPanelResource::collection(
                $this->clases->filtrar($todos, $grupoDe, $fase, $busqueda)->values(),
            ),
            'conteo' => $this->clases->conteoPorFase($segunBusqueda, $grupoDe),
            'total' => $segunBusqueda->count(),
            'hay_grupos' => $todos->isNotEmpty(),
        ]);
    }

    public function miGrupo(Request $request, Grupo $grupo): JsonResponse
    {
        abort_unless(
            $grupo->docente_id === $request->user()->id,
            403,
            'Este grupo no está asignado a tu usuario.',
        );

        $grupo->load(['modulo', 'cohorte.programa', 'aula', 'horarios', 'docente']);

        return response()->json([
            'grupo' => new GrupoPanelResource($grupo),
            'estudiantes' => $this->clases->estudiantesDelGrupo($grupo)->map(fn ($i) => [
                'id' => $i->id,
                'nombre' => $i->matricula?->estudiante?->nombre_completo,
                'correo_institucional' => $i->matricula?->estudiante?->correo_institucional,
                'email' => $i->matricula?->estudiante?->email,
                'estado_matricula' => $i->matricula?->estado?->value,
                'estado_matricula_label' => $i->matricula?->estado?->label(),
            ])->values(),
        ]);
    }

    public function misCursos(Request $request): JsonResponse
    {
        $usuario = $request->user();
        $todas = $this->clases->inscripcionesDelEstudiante($usuario);
        $grupoDe = fn ($i) => $i->grupo;

        $fase = (string) $request->query('fase', '');
        $busqueda = (string) $request->query('q', '');
        $segunBusqueda = $this->clases->filtrar($todas, $grupoDe, null, $busqueda);

        $pendientes = Matricula::where('estudiante_id', $usuario->id)
            ->where('estado', EstadoMatricula::Pendiente->value)
            ->with(['cohorte.programa', 'comprobantes' => fn ($q) => $q->latest()->limit(1)])
            ->withCount('comprobantes')
            ->get();

        return response()->json([
            'data' => $this->clases->filtrar($todas, $grupoDe, $fase, $busqueda)
                ->map(fn (InscripcionGrupo $i) => [
                    'id' => $i->id,
                    'matricula_id' => $i->matricula_id,
                    'matricula_estado' => $i->matricula->estado->value,
                    'matricula_estado_label' => $i->matricula->estado->label(),
                    'grupo' => $this->recursoGrupoParaEstudiante($i),
                ])
                ->values(),
            'conteo' => $this->clases->conteoPorFase($segunBusqueda, $grupoDe),
            'total' => $segunBusqueda->count(),
            'hay_inscripciones' => $todas->isNotEmpty(),
            'pendientes_de_pago' => $pendientes->map(fn (Matricula $m) => [
                'id' => $m->id,
                'edicion' => $m->cohorte?->nombre ?? 'Módulo suelto',
                'programa' => $m->cohorte?->programa?->nombre,
                'fecha_matricula' => $m->fecha_matricula?->format('Y-m-d'),
                'comprobantes' => $m->comprobantes_count,
                'ultimo_comprobante' => $m->comprobantes->first() ? [
                    'estado' => $m->comprobantes->first()->estado->value,
                    'estado_label' => $m->comprobantes->first()->estado->label(),
                    'creado' => $m->comprobantes->first()->created_at?->format('d/m/Y H:i'),
                ] : null,
            ])->values(),
        ]);
    }

    public function miCurso(Request $request, Grupo $grupo): JsonResponse
    {
        $usuario = $request->user();

        $inscripcion = InscripcionGrupo::query()
            ->where('grupo_id', $grupo->id)
            ->whereHas('matricula', fn ($q) => $q->where('estudiante_id', $usuario->id))
            ->with(['matricula', 'grupo.modulo', 'grupo.docente', 'grupo.aula', 'grupo.cohorte.programa', 'grupo.horarios'])
            ->first();

        abort_unless(
            $inscripcion !== null && $inscripcion->matricula->estado !== EstadoMatricula::Retirado,
            403,
            'No tienes acceso a este curso.',
        );

        return response()->json([
            'matricula_id' => $inscripcion->matricula_id,
            'matricula_estado' => $inscripcion->matricula->estado->value,
            'matricula_estado_label' => $inscripcion->matricula->estado->label(),
            'grupo' => $this->recursoGrupoParaEstudiante($inscripcion),
        ]);
    }

    public function calendario(Request $request): JsonResponse
    {
        $grupos = $this->calendario->gruposDe($request->user());
        $mes = $this->calendario->mesSolicitado($request->query('mes'));
        $eventos = $this->calendario->eventosDeGrupos($grupos);

        return response()->json([
            'mes' => $mes->format('Y-m'),
            'mes_label' => $mes->translatedFormat('F \d\e Y'),
            'mes_anterior' => $mes->subMonth()->format('Y-m'),
            'mes_siguiente' => $mes->addMonth()->format('Y-m'),
            'semanas' => array_map(
                fn (array $semana) => array_map(fn (array $dia) => [
                    'numero' => $dia['numero'],
                    'del_mes' => $dia['delMes'],
                    'es_hoy' => $dia['esHoy'],
                    'eventos' => $dia['eventos']->map(fn ($e) => [
                        'tipo' => $e['tipo'],
                        'texto' => $e['texto'],
                    ])->values(),
                ], $semana),
                $this->calendario->semanas($mes, $eventos),
            ),
            'agenda' => $this->calendario->agendaDelMes($eventos, $mes)->map(fn ($e) => [
                'fecha' => $e['fecha']->format('Y-m-d'),
                'tipo' => $e['tipo'],
                'texto' => $e['texto'],
            ])->values(),
        ]);
    }

    private function recursoGrupoParaEstudiante(InscripcionGrupo $inscripcion): GrupoPanelResource
    {
        $puedeVerEnlace = $inscripcion->matricula->estado === EstadoMatricula::Activo
            && in_array($inscripcion->grupo->fase, ['por-iniciar', 'en-curso'], true);

        return (new GrupoPanelResource($inscripcion->grupo))->additional([
            'ocultar_videoconferencia' => ! $puedeVerEnlace,
        ]);
    }
}
