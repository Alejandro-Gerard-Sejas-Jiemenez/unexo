<?php

use App\Http\Middleware\RegistraAcceso;
use App\Http\Middleware\RolRequerido;
use App\Http\Middleware\SoloClienteApi;
use App\Http\Middleware\SoloUsuarioPanel;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Bitácora y contador de visitas: en el grupo web para que ninguna
        // página ni recurso del panel quede fuera del registro.
        $middleware->appendToGroup('web', RegistraAcceso::class);
        $middleware->alias([
            'rol' => RolRequerido::class,
            'cliente.api' => SoloClienteApi::class,
            'usuario.panel' => SoloUsuarioPanel::class,
        ]);
        $proxies = env('TRUSTED_PROXIES', '*');
        $middleware->trustProxies(at: $proxies === '*' ? '*' : array_map('trim', explode(',', (string) $proxies)));
        $middleware->redirectUsersTo('/');
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('api/*') ? null : '/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // La API siempre responde JSON (401/403/404/422), aunque el cliente no mande Accept.
        $exceptions->shouldRenderJsonWhen(fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson());
        // Sin sesión, la API responde 401 en vez de intentar redirigir a un login que no existe.
        $exceptions->render(fn (AuthenticationException $e, Request $request) => $request->is('api/*')
            ? response()->json(['message' => $e->getMessage()], 401)
            : null);
    })->create();
