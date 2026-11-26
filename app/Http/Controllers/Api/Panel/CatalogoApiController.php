<?php

namespace App\Http\Controllers\Api\Panel;

use App\Enums\Certificado;
use App\Enums\EstadoComercial;
use App\Enums\Rol;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CatalogoApiController extends Controller
{
    public function __construct(private readonly CatalogoConsultaService $catalogo) {}

    public function programas(Request $request): AnonymousResourceCollection
    {
        $soloVisibles = $request->user()?->rol !== Rol::Administrador;

        return ProgramaPanelResource::collection(
            $this->catalogo->programas($request->only(['estado', 'atencion', 'q']), $soloVisibles),
        );
    }

    public function programa(Request $request, Programa $programa): ProgramaPanelResource
    {
        if ($request->user()?->rol !== Rol::Administrador) {
            abort_unless($programa->estado_comercial === EstadoComercial::Abierto, 404);
        }

        return new ProgramaPanelResource($programa->load('modulos'));
    }

    public function guardarPrograma(StoreProgramaRequest $request): JsonResponse
    {
        $programa = Programa::create($this->datosConPortada($request));

        return response()->json([
            'programa' => new ProgramaPanelResource($programa),
            'mensaje' => 'Programa creado. Ahora agrega sus módulos.',
        ], 201);
    }

    public function actualizarPrograma(UpdateProgramaRequest $request, Programa $programa): JsonResponse
    {
        $programa->update($this->datosConPortada($request, $programa));

        return response()->json([
            'programa' => new ProgramaPanelResource($programa->fresh()),
            'mensaje' => 'Programa actualizado.',
        ]);
    }

    public function agregarModulo(Request $request, Programa $programa, OrdenModulosService $orden): JsonResponse
    {
        $datos = $request->validate(
            ['modulo_id' => ['required', 'uuid', 'exists:modulos,id']],
            [],
            ['modulo_id' => 'módulo'],
        );

        if (! $orden->agregar($programa, $datos['modulo_id'])) {
            throw ValidationException::withMessages([
                'modulo_id' => 'Ese módulo ya pertenece al plan de estudio del programa.',
            ]);
        }

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
        $datos = $request->validate(['direccion' => ['required', 'in:subir,bajar']]);

        $orden->mover($programa, $modulo, $datos['direccion']);

        return response()->json([
            'programa' => new ProgramaPanelResource($programa->fresh()->load('modulos')),
            'mensaje' => 'Orden actualizado.',
        ]);
    }

    public function modulos(Request $request): AnonymousResourceCollection
    {
        $soloVisibles = $request->user()?->rol !== Rol::Administrador;

        return ModuloPanelResource::collection(
            $this->catalogo->modulos($request->only(['estado', 'q']), $soloVisibles),
        );
    }

    public function modulo(Request $request, Modulo $modulo): ModuloPanelResource
    {
        if ($request->user()?->rol !== Rol::Administrador) {
            abort_unless($modulo->estado_comercial === EstadoComercial::Abierto, 404);
        }

        return new ModuloPanelResource($modulo);
    }

    public function guardarModulo(StoreModuloRequest $request): JsonResponse
    {
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

    public function opciones(Request $request): JsonResponse
    {
        $programaId = $request->query('programa');
        $programaValido = is_string($programaId) && Str::isUuid($programaId) ? $programaId : null;

        $disponibles = collect();
        $totalDisponibles = 0;

        if ($programaValido) {
            $consulta = Modulo::whereDoesntHave('programas', fn ($q) => $q->where('programas.id', $programaValido))
                ->orderBy('nombre');
            $totalDisponibles = $consulta->count();
            $disponibles = $consulta->limit(config('limites.opciones_select'))->get();
        }

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
                'opciones_select' => config('limites.opciones_select'),
            ],
            'modulos_disponibles' => ModuloPanelResource::collection($disponibles),
            'modulos_disponibles_total' => $totalDisponibles,
        ]);
    }

    private function datosConPortada(Request $request, ?Programa $actual = null): array
    {
        $data = $request->safe()->except('imagen_portada_url');

        if ($request->hasFile('imagen_portada_url')) {
            if ($actual?->imagen_portada_url && str_starts_with($actual->imagen_portada_url, '/storage/programas/')) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $actual->imagen_portada_url));
            }
            $path = $request->file('imagen_portada_url')->store('programas', 'public');
            $data['imagen_portada_url'] = '/storage/'.$path;
        }

        return $data;
    }
}
