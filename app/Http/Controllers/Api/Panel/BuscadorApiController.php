<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\BuscarRequest;
use App\Services\BuscadorService;
use Illuminate\Http\JsonResponse;

class BuscadorApiController extends Controller
{
    public function __invoke(BuscarRequest $request, BuscadorService $buscador): JsonResponse
    {
        return response()->json(
            $buscador->buscar((string) ($request->validated('q') ?? ''), $request->user()),
        );
    }
}
