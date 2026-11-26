<?php

namespace App\Http\Controllers\Api\Panel;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Requests\PerfilRequest;
use App\Http\Requests\UsuarioRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\User;
use App\Services\UsuarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class UsuarioApiController extends Controller
{
    public function __construct(private readonly UsuarioService $usuarios)
    {
    }

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
        $datos = $this->conFoto($request, $request->datosParaGuardar());
        $usuario = User::create($datos);

        return response()->json(
            ['usuario' => new UsuarioResource($usuario), 'mensaje' => 'Usuario creado correctamente.'],
            201,
        );
    }

    public function update(UsuarioRequest $request, User $usuario): JsonResponse
    {
        $datos = $this->conFoto($request, $request->datosParaGuardar(), $usuario);
        $usuario->update($datos);

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

    public function opciones(): JsonResponse
    {
        return response()->json([
            'roles' => array_map(fn(Rol $r) => ['valor' => $r->value, 'etiqueta' => $r->label()], Rol::cases()),
            'limites' => config('limites.usuarios'),
        ]);
    }

    public function perfil(Request $request): JsonResponse
    {
        return response()->json([
            'usuario' => new UsuarioResource($request->user()),
        ]);
    }

    public function actualizarPerfil(PerfilRequest $request): JsonResponse
    {
        $usuario = $request->user();
        $datos = $request->safe()->except(['foto_perfil', 'foto_perfil_url', 'password_confirmation']);

        if (empty($datos['password'])) {
            unset($datos['password']);
        }

        $datos = $this->conFoto($request, $datos, $usuario);
        $usuario->update($datos);

        return response()->json([
            'usuario' => new UsuarioResource($usuario->fresh()),
            'mensaje' => 'Perfil actualizado correctamente.',
        ]);
    }

    private function conFoto(Request $request, array $datos, ?User $actual = null): array
    {
        if (!$request->hasFile('foto_perfil')) {
            return $datos;
        }

        if ($actual?->foto_perfil_url && str_starts_with($actual->foto_perfil_url, '/storage/avatares/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $actual->foto_perfil_url));
        }

        $ruta = $request->file('foto_perfil')->store('avatares', 'public');
        $datos['foto_perfil_url'] = '/storage/' . $ruta;

        return $datos;
    }
}
