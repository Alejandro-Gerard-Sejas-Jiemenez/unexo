<?php

namespace App\Http\Middleware;

use App\Enums\Rol;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RolRequerido
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Quien llega puede no ser un usuario de negocio: un token de cliente de la API
        // resuelve un ClienteApi, que no tiene rol. En ese caso también se rechaza.
        $rol = $request->user()?->rol;

        if (! $rol instanceof Rol || ! in_array($rol->value, $roles, true)) {
            abort(403, 'No tienes permiso para ver esta página.');
        }

        return $next($request);
    }
}
