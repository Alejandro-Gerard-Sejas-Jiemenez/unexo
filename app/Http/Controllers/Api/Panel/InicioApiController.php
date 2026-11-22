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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pantalla de inicio y catálogo público: lo que cada rol ve al entrar.
 */
class InicioApiController extends Controller
{
    /** El contenido del inicio depende del rol, igual que en el panel Blade. */
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
                'avisos' => $resumen->avisos(),
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

    /**
     * Catálogo público: la misma oferta que ve el CRM, en el formato que la
     * pantalla necesita. Lo consultan los tres roles.
     */
    public function catalogo(Request $request): JsonResponse
    {
        $busqueda = trim((string) $request->query('q', ''));
        $visibles = [EstadoPlanificacion::Programado->value, EstadoPlanificacion::EnCurso->value];

        $programas = Programa::visibleParaCrm()
            ->with([
                'modulos' => fn ($q) => $q->where('modulos.estado_comercial', EstadoComercial::Abierto->value),
                'cohortes' => fn ($q) => $q->whereIn('estado', $visibles)->orderBy('fecha_inicio'),
                'cohortes.grupos' => fn ($q) => $q->with(['modulo', 'docente', 'aula']),
            ])
            ->when($busqueda !== '', fn ($q) => $this->filtrarTexto($q, $busqueda))
            ->orderBy('nombre')
            ->get();

        $modulos = Modulo::visibleParaCrm()
            ->with([
                'programas',
                'grupos' => fn ($q) => $q->whereNull('cohorte_id')
                    ->whereIn('estado', $visibles)
                    ->with(['docente', 'aula'])
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
                'certificado' => $p->certificado_otorga?->label(),
                'modulos' => $p->modulos->map(fn (Modulo $m) => [
                    'id' => $m->id,
                    'nombre' => $m->nombre,
                    'duracion_horas' => $m->duracion_horas,
                ])->values(),
                'ediciones' => $p->cohortes->map(fn ($c) => [
                    'id' => $c->id,
                    'nombre' => $c->nombre,
                    'fecha_inicio' => $c->fecha_inicio?->format('Y-m-d'),
                    'fecha_fin' => $c->fecha_fin?->format('Y-m-d'),
                    'grupos' => $c->grupos->count(),
                ])->values(),
            ]),
            'modulos' => $modulos->map(fn (Modulo $m) => [
                'id' => $m->id,
                'codigo' => $m->codigo,
                'nombre' => $m->nombre,
                'descripcion' => $m->descripcion,
                'duracion_horas' => $m->duracion_horas,
                'prerrequisito' => $m->prerrequisito,
                'programas' => $m->programas->pluck('nombre')->values(),
                // Grupos sueltos: los que se pueden cursar sin entrar a un programa.
                'grupos_sueltos' => GrupoPanelResource::collection($m->grupos),
            ]),
        ]);
    }

    private function filtrarTexto($consulta, string $busqueda)
    {
        return $consulta->where(fn ($b) => $b
            ->where('nombre', 'like', "%{$busqueda}%")
            ->orWhere('codigo', 'like', "%{$busqueda}%")
            ->orWhere('descripcion', 'like', "%{$busqueda}%"));
    }
}
