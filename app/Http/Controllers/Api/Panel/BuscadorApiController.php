<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Services\BuscadorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BuscadorApiController extends Controller
{
    public function __invoke(Request $request, BuscadorService $buscador): JsonResponse
    {
        $datos = $request->validate(
            ['q' => ['nullable', 'string', 'max:100']],
            [],
            ['q' => 'término de búsqueda'],
        );

        return response()->json($buscador->buscar((string) ($datos['q'] ?? ''), $request->user()));
    }
}
