<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SoloUsuarioPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (! $usuario instanceof User) {
            return response()->json([
                'message' => 'Solo los usuarios del panel pueden acceder a este recurso.',
            ], 403);
        }

        if (! $usuario->estaActivo()) {
            if ($request->hasSession()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return response()->json([
                'message' => 'Tu cuenta está inactiva. Contacta al administrador.',
            ], 401);
        }

        return $next($request);
    }
}
