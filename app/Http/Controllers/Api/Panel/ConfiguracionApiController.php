<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\AulaRequest;
use App\Http\Requests\ClienteApiRequest;
use App\Models\Aula;
use App\Models\ClienteApi;
use Illuminate\Http\JsonResponse;

class ConfiguracionApiController extends Controller
{
    public function aulas(): JsonResponse
    {
        $pagina = Aula::withCount('grupos')
            ->orderBy('nombre')
            ->paginate(config('limites.por_pagina'));

        return response()->json([
            'data' => $pagina->getCollection()->map(fn (Aula $a) => [
                'id' => $a->id,
                'nombre' => $a->nombre,
                'capacidad' => $a->capacidad,
                'grupos_count' => $a->grupos_count,
            ]),
            'meta' => [
                'current_page' => $pagina->currentPage(),
                'last_page' => $pagina->lastPage(),
                'per_page' => $pagina->perPage(),
                'total' => $pagina->total(),
            ],
            'limites' => config('limites.aulas'),
        ]);
    }

    public function guardarAula(AulaRequest $request): JsonResponse
    {
        $aula = Aula::create($request->validated());

        return response()->json(['aula' => $aula, 'mensaje' => 'Aula registrada correctamente.'], 201);
    }

    public function actualizarAula(AulaRequest $request, Aula $aula): JsonResponse
    {
        $aula->update($request->validated());

        return response()->json(['aula' => $aula->fresh(), 'mensaje' => 'Aula actualizada correctamente.']);
    }

    public function clientesApi(): JsonResponse
    {
        $pagina = ClienteApi::withCount('tokens')
            ->orderBy('nombre')
            ->paginate(config('limites.por_pagina'));

        return response()->json([
            'data' => $pagina->getCollection()->map(fn (ClienteApi $c) => [
                'id' => $c->id,
                'nombre' => $c->nombre,
                'tokens_count' => $c->tokens_count,
                'creado' => $c->created_at?->format('d/m/Y H:i'),
            ]),
            'meta' => [
                'current_page' => $pagina->currentPage(),
                'last_page' => $pagina->lastPage(),
                'per_page' => $pagina->perPage(),
                'total' => $pagina->total(),
            ],
        ]);
    }

    public function crearClienteApi(ClienteApiRequest $request): JsonResponse
    {
        $cliente = ClienteApi::create($request->validated() + ['creado_por' => $request->user()->id]);

        return response()->json([
            'cliente' => ['id' => $cliente->id, 'nombre' => $cliente->nombre],
            'token' => $cliente->createToken('crm')->plainTextToken,
            'mensaje' => "Cliente «{$cliente->nombre}» creado. Copia el token ahora: no se vuelve a mostrar.",
        ], 201);
    }

    public function revocarClienteApi(ClienteApi $cliente): JsonResponse
    {
        $cliente->tokens()->delete();
        $cliente->delete();

        return response()->json(['mensaje' => 'Acceso revocado.']);
    }
}
