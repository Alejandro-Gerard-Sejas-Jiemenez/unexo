<?php

namespace App\Http\Controllers\Api\Panel;

use App\Enums\Certificado;
use App\Enums\EstadoComercial;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreModuloRequest;
use App\Http\Requests\StoreProgramaRequest;
use App\Http\Requests\UpdateModuloRequest;
use App\Http\Requests\UpdateProgramaRequest;
use App\Http\Resources\ModuloPanelResource;
use App\Http\Resources\ProgramaPanelResource;
use App\Models\Modulo;
use App\Models\Programa;
use App\Services\CatalogoConsultaService;
use App\Services\OrdenModulosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * CU3: catálogo académico y oferta comercial.
 * Usa las mismas Form Requests que el panel Blade, así que las reglas de
 * validación no se escriben dos veces.
 */
class CatalogoApiController extends Controller
{
    public function __construct(private readonly CatalogoConsultaService $catalogo) {}

    // --------------------------------------------------------------- programas

    public function programas(Request $request): AnonymousResourceCollection
    {
        return ProgramaPanelResource::collection(
            $this->catalogo->programas($request->only(['estado', 'atencion', 'q'])),
        );
    }

    public function programa(Programa $programa): ProgramaPanelResource
    {
        return new ProgramaPanelResource($programa->load('modulos'));
    }

    public function guardarPrograma(StoreProgramaRequest $request): JsonResponse
    {
        $programa = Programa::create($request->safe()->except('imagen_portada_url'));

        return response()->json([
            'programa' => new ProgramaPanelResource($programa),
            'mensaje' => 'Programa creado. Ahora agrega sus módulos.',
        ], 201);
    }

    public function actualizarPrograma(UpdateProgramaRequest $request, Programa $programa): JsonResponse
    {
        $programa->update($request->safe()->except('imagen_portada_url'));

        return response()->json([
            'programa' => new ProgramaPanelResource($programa->fresh()),
            'mensaje' => 'Programa actualizado.',
        ]);
    }

    // ------------------------------------------------------- plan de estudio

    public function agregarModulo(Request $request, Programa $programa, OrdenModulosService $orden): JsonResponse
    {
        $datos = $request->validate(
            ['modulo_id' => ['required', 'uuid', 'exists:modulos,id']],
            [],
            ['modulo_id' => 'módulo'],
        );

        $orden->agregar($programa, $datos['modulo_id']);

        return response()->json([
            'programa' => new ProgramaPanelResource($programa->fresh()->load('modulos')),
            'mensaje' => 'Módulo agregado al plan de estudio.',
        ]);
    }

    public function quitarModulo(Programa $programa, Modulo $modulo, OrdenModulosService $orden): JsonResponse
    {
        $orden->quitar($programa, $modulo);

        return response()->json([
            'programa' => new ProgramaPanelResource($programa->fresh()->load('modulos')),
            'mensaje' => 'Módulo quitado del plan de estudio.',
        ]);
    }

    public function moverModulo(Request $request, Programa $programa, Modulo $modulo, OrdenModulosService $orden): JsonResponse
    {
        // Mismo vocabulario que el panel Blade y que OrdenModulosService.
        $datos = $request->validate(['direccion' => ['required', 'in:subir,bajar']]);

        $orden->mover($programa, $modulo, $datos['direccion']);

        return response()->json([
            'programa' => new ProgramaPanelResource($programa->fresh()->load('modulos')),
            'mensaje' => 'Orden actualizado.',
        ]);
    }

    // ----------------------------------------------------------------- módulos

    public function modulos(Request $request): AnonymousResourceCollection
    {
        return ModuloPanelResource::collection(
            $this->catalogo->modulos($request->only(['estado', 'q'])),
        );
    }

    public function modulo(Modulo $modulo): ModuloPanelResource
    {
        return new ModuloPanelResource($modulo);
    }

    public function guardarModulo(StoreModuloRequest $request): JsonResponse
    {
        // El temario del módulo es una URL, no una subida: va tal cual.
        $modulo = Modulo::create($request->validated());

        return response()->json([
            'modulo' => new ModuloPanelResource($modulo),
            'mensaje' => 'Módulo creado correctamente.',
        ], 201);
    }

    public function actualizarModulo(UpdateModuloRequest $request, Modulo $modulo): JsonResponse
    {
        $modulo->update($request->validated());

        return response()->json([
            'modulo' => new ModuloPanelResource($modulo->fresh()),
            'mensaje' => 'Módulo actualizado correctamente.',
        ]);
    }

    /** Opciones de los selects y módulos libres para armar el plan de estudio. */
    public function opciones(Request $request): JsonResponse
    {
        $programaId = $request->query('programa');

        return response()->json([
            'estados' => array_map(
                fn (EstadoComercial $e) => ['valor' => $e->value, 'etiqueta' => $e->label()],
                EstadoComercial::cases(),
            ),
            'certificados' => array_map(
                fn (Certificado $c) => ['valor' => $c->value, 'etiqueta' => $c->label()],
                Certificado::cases(),
            ),
            'limites' => [
                'programas' => config('limites.programas'),
                'modulos' => config('limites.modulos'),
            ],
            'modulos_disponibles' => $programaId
                ? ModuloPanelResource::collection(
                    Modulo::whereDoesntHave('programas', fn ($q) => $q->where('programas.id', $programaId))
                        ->orderBy('nombre')
                        ->limit(config('limites.opciones_select'))
                        ->get(),
                )
                : [],
        ]);
    }
}
