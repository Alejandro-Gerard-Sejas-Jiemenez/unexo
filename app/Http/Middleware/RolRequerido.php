<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RolRequerido
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        if (! $usuario || ! in_array($usuario->rol->value, $roles, true)) {
            abort(403, 'No tienes permiso para ver esta página.');
        }

        return $next($request);
    }
}
