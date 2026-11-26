<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Services\MenuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Barra de navegación dinámica: la construye el servidor con los permisos del rol
 * activo, así la interfaz nunca ofrece una opción que el servidor rechazaría.
 */
class MenuController extends Controller
{
    public function __invoke(Request $request, MenuService $menu): JsonResponse
    {
        return response()->json(['menu' => $menu->paraSpa($request->user())]);
    }
}
