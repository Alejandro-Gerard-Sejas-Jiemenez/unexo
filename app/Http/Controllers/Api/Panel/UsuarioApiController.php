<?php

namespace App\Http\Controllers\Api\Panel;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Requests\UsuarioRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\User;
use App\Services\UsuarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UsuarioApiController extends Controller
{
    public function __construct(private readonly UsuarioService $usuarios) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $pagina = $this->usuarios->listar($request->only(['rol', 'estado', 'q']));

        return UsuarioResource::collection($pagina);
    }

    public function show(User $usuario): UsuarioResource
    {
        return new UsuarioResource($usuario);
    }

    public function store(UsuarioRequest $request): JsonResponse
    {
        $usuario = User::create($request->datosParaGuardar());

        return response()->json(
            ['usuario' => new UsuarioResource($usuario), 'mensaje' => 'Usuario creado correctamente.'],
            201,
        );
    }

    public function update(UsuarioRequest $request, User $usuario): JsonResponse
    {
        $usuario->update($request->datosParaGuardar());

        return response()->json([
            'usuario' => new UsuarioResource($usuario->fresh()),
            'mensaje' => 'Usuario actualizado correctamente.',
        ]);
    }

    public function cambiarEstado(Request $request, User $usuario): JsonResponse
    {
        $error = $this->usuarios->alternarEstado($usuario, $request->user());

        if ($error !== null) {
            return response()->json(['message' => $error, 'errors' => ['usuario' => [$error]]], 422);
        }

        return response()->json([
            'usuario' => new UsuarioResource($usuario->fresh()),
            'mensaje' => 'Estado actualizado.',
        ]);
    }

    /** Opciones para los selects del formulario, tomadas del enum y de la configuración. */
    public function opciones(): JsonResponse
    {
        return response()->json([
            'roles' => array_map(fn (Rol $r) => ['valor' => $r->value, 'etiqueta' => $r->label()], Rol::cases()),
            'limites' => config('limites.usuarios'),
        ]);
    }
}
