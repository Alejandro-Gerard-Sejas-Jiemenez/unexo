<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CrmEdicionResource;
use App\Http\Resources\CrmModuloResource;
use App\Http\Resources\CrmProgramaResource;
use App\Services\CrmCatalogoService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Endpoints de catálogo académico y oferta comercial para el CRM externo.
 */
class CrmCatalogoApiController extends Controller
{
    public function __construct(private readonly CrmCatalogoService $catalogo) {}

    /**
     * Paquete completo de oferta académica disponible (programas, módulos y ediciones abiertas).
     */
    public function index(): JsonResponse
    {
        $programas = $this->catalogo->programasVisibles();
        $modulos = $this->catalogo->modulosVisibles();

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
     * Listado de programas visibles; el parámetro `nivel` ya no existe y se ignora.
     */
    public function programas(Request $request): AnonymousResourceCollection
    {
        $busqueda = trim((string) $request->query('q', ''));

        return CrmProgramaResource::collection($this->catalogo->programasVisibles($busqueda));
    }

    /**
     * Detalle técnico y oferta comercial de un programa visible.
     */
    public function programa(string $programa): CrmProgramaResource
    {
        try {
            return new CrmProgramaResource($this->catalogo->programaVisible($programa));
        } catch (ModelNotFoundException) {
            abort(404, 'El programa solicitado no está disponible comercialmente.');
        }
    }

    /**
     * Ediciones programadas o en curso de programas visibles, con sus grupos.
     */
    public function ediciones(Request $request): AnonymousResourceCollection
    {
        $ediciones = $this->catalogo->edicionesVisibles(
            $request->query('programa_id'),
            $request->query('estado'),
        );

        return CrmEdicionResource::collection($ediciones);
    }

    /**
     * Detalle de una edición visible.
     */
    public function edicion(string $cohorte): CrmEdicionResource
    {
        try {
            return new CrmEdicionResource($this->catalogo->edicionVisible($cohorte));
        } catch (ModelNotFoundException) {
            abort(404, 'La edición solicitada no está disponible.');
        }
    }

    /**
     * Listado de módulos abiertos ofertables.
     */
    public function modulos(Request $request): AnonymousResourceCollection
    {
        $busqueda = trim((string) $request->query('q', ''));

        return CrmModuloResource::collection($this->catalogo->modulosVisibles($busqueda));
    }

    /**
     * Detalle de un módulo abierto.
     */
    public function modulo(string $modulo): CrmModuloResource
    {
        try {
            return new CrmModuloResource($this->catalogo->moduloVisible($modulo));
        } catch (ModelNotFoundException) {
            abort(404, 'El módulo solicitado no está disponible comercialmente.');
        }
    }
}
