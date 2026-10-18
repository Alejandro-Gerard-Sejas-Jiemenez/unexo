<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoComercial;
use App\Http\Controllers\Controller;
use App\Http\Resources\CrmModuloResource;
use App\Http\Resources\CrmProgramaResource;
use App\Models\Modulo;
use App\Models\Programa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Endpoints de catálogo académico y oferta comercial para el CRM externo.
 */
class CrmCatalogoApiController extends Controller
{
    /**
     * Paquete completo de oferta académica disponible (programas, módulos y ediciones abiertas).
     */
    public function index(): JsonResponse
    {
        $programas = Programa::visibleParaCrm()
            ->with([
                'modulos' => fn ($q) => $q->where('modulos.estado_comercial', EstadoComercial::Abierto->value),
                'cohortes' => fn ($q) => $q->orderBy('fecha_inicio'),
                'cohortes.grupos.modulo',
                'cohortes.grupos.docente',
                'cohortes.grupos.aula',
            ])
            ->orderBy('nombre')
            ->get();

        $modulos = Modulo::visibleParaCrm()
            ->with([
                'programas',
                'grupos' => fn ($q) => $q->whereNull('cohorte_id')->with(['docente', 'aula'])->orderBy('fecha_inicio'),
            ])
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'exito' => true,
            'fecha_consulta' => now()->toIso8601String(),
            'resumen' => [
                'total_programas_abiertos' => $programas->count(),
                'total_modulos_libres' => $modulos->count(),
            ],
            'programas' => CrmProgramaResource::collection($programas),
            'modulos_libres' => CrmModuloResource::collection($modulos),
        ]);
    }

    /**
     * Listado de programas abiertos para el CRM con opción de búsqueda.
     */
    public function programas(Request $request): AnonymousResourceCollection
    {
        $busqueda = trim((string) $request->query('q', ''));
        $nivel = trim((string) $request->query('nivel', ''));

        $programas = Programa::visibleParaCrm()
            ->with([
                'modulos' => fn ($q) => $q->where('modulos.estado_comercial', EstadoComercial::Abierto->value),
                'cohortes' => fn ($q) => $q->orderBy('fecha_inicio'),
                'cohortes.grupos.modulo',
                'cohortes.grupos.docente',
                'cohortes.grupos.aula',
            ])
            ->when($busqueda !== '', fn ($q) => $q->where(fn ($b) => $b
                ->where('nombre', 'like', "%{$busqueda}%")
                ->orWhere('codigo', 'like', "%{$busqueda}%")
                ->orWhere('descripcion', 'like', "%{$busqueda}%")
            ))
            ->when($nivel !== '', fn ($q) => $q->where('nivel_academico', $nivel))
            ->orderBy('nombre')
            ->get();

        return CrmProgramaResource::collection($programas);
    }

    /**
     * Detalle técnico y oferta comercial de un programa específico.
     */
    public function programa(Programa $programa): CrmProgramaResource
    {
        abort_unless(
            $programa->estado_comercial === EstadoComercial::Abierto,
            404,
            'El programa solicitado no está disponible comercialmente.'
        );

        $programa->loadMissing([
            'modulos' => fn ($q) => $q->where('modulos.estado_comercial', EstadoComercial::Abierto->value),
            'cohortes' => fn ($q) => $q->orderBy('fecha_inicio'),
            'cohortes.grupos.modulo',
            'cohortes.grupos.docente',
            'cohortes.grupos.aula',
        ]);

        return new CrmProgramaResource($programa);
    }

    /**
     * Listado de módulos abiertos ofertables como cursos independientes.
     */
    public function modulos(Request $request): AnonymousResourceCollection
    {
        $busqueda = trim((string) $request->query('q', ''));

        $modulos = Modulo::visibleParaCrm()
            ->with([
                'programas',
                'grupos' => fn ($q) => $q->whereNull('cohorte_id')->with(['docente', 'aula'])->orderBy('fecha_inicio'),
            ])
            ->when($busqueda !== '', fn ($q) => $q->where(fn ($b) => $b
                ->where('nombre', 'like', "%{$busqueda}%")
                ->orWhere('codigo', 'like', "%{$busqueda}%")
                ->orWhere('descripcion', 'like', "%{$busqueda}%")
            ))
            ->orderBy('nombre')
            ->get();

        return CrmModuloResource::collection($modulos);
    }

    /**
     * Detalle de un módulo individual ofertable.
     */
    public function modulo(Modulo $modulo): CrmModuloResource
    {
        abort_unless(
            $modulo->estado_comercial === EstadoComercial::Abierto,
            404,
            'El módulo solicitado no está disponible comercialmente.'
        );

        $modulo->loadMissing([
            'programas',
            'grupos' => fn ($q) => $q->whereNull('cohorte_id')->with(['docente', 'aula'])->orderBy('fecha_inicio'),
        ]);

        return new CrmModuloResource($modulo);
    }
}
