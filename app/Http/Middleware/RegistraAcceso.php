<?php

namespace App\Http\Middleware;

use App\Services\BitacoraService;
use App\Services\VisitaService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RegistraAcceso
{
    public function __construct(
        private readonly BitacoraService $bitacora,
        private readonly VisitaService $visitas,
    ) {}

    public function handle(Request $peticion, Closure $next): Response
    {
        $respuesta = $next($peticion);

        $this->sinRomper(function () use ($peticion, $respuesta) {
            if ($this->debeContarVisita($peticion, $respuesta)) {
                $this->visitas->registrar($peticion->path());
            }
        });

        return $respuesta;
    }

    public function terminate(Request $peticion, Response $respuesta): void
    {
        $this->sinRomper(function () use ($peticion, $respuesta) {
            if ($this->debeAuditar($peticion)) {
                $this->bitacora->registrar('acceso_recurso', $peticion, [
                    'codigo_respuesta' => $respuesta->getStatusCode(),
                ]);
            }
        });
    }

    private function sinRomper(callable $accion): void
    {
        try {
            $accion();
        } catch (Throwable $e) {
            Log::warning('No se pudo registrar el acceso: '.$e->getMessage());
        }
    }

    private function debeContarVisita(Request $peticion, Response $respuesta): bool
    {
        if (! $peticion->isMethod('GET') || $peticion->is('api/*', 'app', 'app/*') || $peticion->ajax()) {
            return false;
        }

        return $respuesta->getStatusCode() === 200
            && str_contains((string) $respuesta->headers->get('Content-Type'), 'text/html');
    }

    private function debeAuditar(Request $peticion): bool
    {
        if ($peticion->is(
            'api/panel/login',
            'api/panel/logout',
            'api/panel/visitas',
            'api/panel/me',
            'api/panel/menu',
            'api/panel/bitacora',
            'login',
            'logout',
        )) {
            return false;
        }

        return $peticion->user() !== null;
    }
}
