<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Services\SaludService;
use Illuminate\Http\JsonResponse;

class SaludApiController extends Controller
{
    public function __invoke(SaludService $salud): JsonResponse
    {
        return response()->json($salud->diagnosticar());
    }
}
