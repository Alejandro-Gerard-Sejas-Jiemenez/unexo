<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Models\Aula;
use App\Models\ClienteApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Configuración del administrador: aulas y clientes de la API (CU28).
 */
class ConfiguracionApiController extends Controller
{
    // ------------------------------------------------------------- aulas

    public function aulas(): JsonResponse
    {
        return response()->json([
            'data' => Aula::withCount('grupos')->orderBy('nombre')->get()->map(fn (Aula $a) => [
                'id' => $a->id,
                'nombre' => $a->nombre,
                'capacidad' => $a->capacidad,
                'grupos_count' => $a->grupos_count,
            ]),
            'limites' => config('limites.aulas'),
        ]);
    }

    public function guardarAula(Request $request): JsonResponse
    {
        $aula = Aula::create($this->validarAula($request, null));

        return response()->json(['aula' => $aula, 'mensaje' => 'Aula registrada correctamente.'], 201);
    }

    public function actualizarAula(Request $request, Aula $aula): JsonResponse
    {
        $aula->update($this->validarAula($request, $aula));

        return response()->json(['aula' => $aula->fresh(), 'mensaje' => 'Aula actualizada correctamente.']);
    }

    private function validarAula(Request $request, ?Aula $aula): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:'.config('limites.aulas.nombre'), Rule::unique('aulas', 'nombre')->ignore($aula)],
            'capacidad' => ['nullable', 'integer', 'min:1', 'max:'.config('limites.aulas.capacidad')],
        ], [], ['nombre' => 'nombre del aula']);
    }

    // ------------------------------------------------- clientes de la API

    public function clientesApi(): JsonResponse
    {
        return response()->json([
            'data' => ClienteApi::withCount('tokens')->orderBy('nombre')->get()
                ->map(fn (ClienteApi $c) => [
                    'id' => $c->id,
                    'nombre' => $c->nombre,
                    'tokens_count' => $c->tokens_count,
                    'creado' => $c->created_at?->format('d/m/Y'),
                ]),
        ]);
    }

    public function crearClienteApi(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:80', 'unique:api_clientes,nombre'],
        ], [], ['nombre' => 'nombre del cliente']);

        $cliente = ClienteApi::create($datos);

        return response()->json([
            'cliente' => ['id' => $cliente->id, 'nombre' => $cliente->nombre],
            // El token se muestra una sola vez: en la base queda solo su hash.
            'token' => $cliente->createToken('crm')->plainTextToken,
            'mensaje' => "Cliente «{$cliente->nombre}» creado. Copiá el token ahora: no se vuelve a mostrar.",
        ], 201);
    }

    public function revocarClienteApi(ClienteApi $cliente): JsonResponse
    {
        $cliente->tokens()->delete();
        $cliente->delete();

        return response()->json(['mensaje' => 'Acceso revocado.']);
    }
}
