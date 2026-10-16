<?php

namespace App\Http\Middleware;

use App\Models\ClienteApi;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SoloClienteApi
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user() instanceof ClienteApi, 403, 'Este token no pertenece a un cliente de la API.');

        return $next($request);
    }
}
