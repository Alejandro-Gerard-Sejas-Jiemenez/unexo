<?php

namespace App\Http\Controllers\Api\Panel;

use App\Enums\EstadoComercial;
use App\Enums\EstadoPlanificacion;
use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Resources\GrupoPanelResource;
use App\Models\Modulo;
use App\Models\Programa;
use App\Services\MisClasesService;
use App\Services\ResumenCatalogoService;
use App\Support\ConsultaTexto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InicioApiController extends Controller
{
    public function inicio(
        Request $request,
        ResumenCatalogoService $resumen,
        MisClasesService $clases,
    ): JsonResponse {
        $usuario = $request->user();

        return response()->json(match ($usuario->rol) {
            Rol::Administrador => [
                'rol' => 'administrador',
                'oferta' => $resumen->ofertaVisibleParaCrm(),
                'avisos' => $resumen->avisosSpa(),
                'totales' => $resumen->totales(),
            ],
            Rol::Docente => [
                'rol' => 'docente',
                'proximos' => GrupoPanelResource::collection(
                    $clases->proximos($clases->gruposDelDocente($usuario), fn ($g) => $g),
                ),
            ],
            Rol::Estudiante => [
                'rol' => 'estudiante',
                'proximos' => GrupoPanelResource::collection(
                    $clases->proximos(
                        $clases->inscripcionesDelEstudiante($usuario),
                        fn ($i) => $i->grupo,
                    )->map(fn ($i) => $i->grupo),
                ),
            ],
        });
    }

    public function catalogo(Request $request): JsonResponse
    {
        $busqueda = trim((string) $request->query('q', ''));
        $visibles = [EstadoPlanificacion::Programado->value, EstadoPlanificacion::EnCurso->value];

        $programas = Programa::visibleParaCrm()
            ->with([
                'modulos' => fn ($q) => $q->where('modulos.estado_comercial', EstadoComercial::Abierto->value),
                'cohortes' => fn ($q) => $q->whereIn('estado', $visibles)->withCount('grupos')->orderBy('fecha_inicio'),
            ])
            ->when($busqueda !== '', fn ($q) => $this->filtrarTexto($q, $busqueda))
            ->orderBy('nombre')
            ->get();

        $modulos = Modulo::visibleParaCrm()
            ->with([
                'programas:id,nombre',
                'grupos' => fn ($q) => $q->whereNull('cohorte_id')
                    ->whereIn('estado', $visibles)
                    ->with(['modulo:id,nombre,codigo,duracion_horas', 'docente:id,nombres,apellidos', 'aula:id,nombre'])
                    ->orderBy('fecha_inicio'),
            ])
            ->when($busqueda !== '', fn ($q) => $this->filtrarTexto($q, $busqueda))
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'programas' => $programas->map(fn (Programa $p) => [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'nombre' => $p->nombre,
                'version' => $p->version,
                'descripcion' => $p->descripcion,
                'precio' => $p->precio !== null ? (float) $p->precio : null,
                'imagen_portada_url' => $p->imagen_portada_url,
                'certificado' => $p->certificado_otorga?->label(),
                'duracion_total_horas' => (int) $p->modulos->sum('duracion_horas'),
                'modulos' => $p->modulos->map(fn (Modulo $m) => [
                    'id' => $m->id,
                    'nombre' => $m->nombre,
                    'duracion_horas' => $m->duracion_horas,
                ])->values(),
                'ediciones' => $p->cohortes->map(fn ($c) => [
                    'id' => $c->id,
                    'nombre' => $c->nombre,
                    'estado' => $c->estado->value,
                    'estado_label' => $c->estado->label(),
                    'fecha_inicio' => $c->fecha_inicio?->format('Y-m-d'),
                    'fecha_fin' => $c->fecha_fin?->format('Y-m-d'),
                    'grupos' => $c->grupos_count,
                ])->values(),
            ]),
            'modulos' => $modulos->map(fn (Modulo $m) => [
                'id' => $m->id,
                'codigo' => $m->codigo,
                'nombre' => $m->nombre,
                'descripcion' => $m->descripcion,
                'duracion_horas' => $m->duracion_horas,
                'prerrequisito' => $m->prerrequisito,
                'pdf_temario_url' => $m->pdf_temario_url,
                'programas' => $m->programas->pluck('nombre')->values(),
                'grupos_sueltos' => GrupoPanelResource::collection($m->grupos),
            ]),
        ]);
    }

    private function filtrarTexto($consulta, string $busqueda)
    {
        return $consulta->where(function ($b) use ($busqueda) {
            ConsultaTexto::dondeContiene($b, 'nombre', $busqueda);
            ConsultaTexto::dondeContiene($b, 'codigo', $busqueda, true);
            ConsultaTexto::dondeContiene($b, 'descripcion', $busqueda, true);
        });
    }
}
