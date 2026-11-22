<?php

namespace App\Http\Middleware;

use App\Services\BitacoraService;
use App\Services\VisitaService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Bitácora de auditoría y contador de visitas, aplicados desde el middleware
 * para que ninguna ruta quede fuera del registro por olvido de un controlador.
 *
 * Son efectos secundarios: si fallan, la petición del usuario debe responder
 * igual. Un disco lleno o una tabla bloqueada no pueden tumbar el sitio.
 */
class RegistraAcceso
{
    public function __construct(
        private readonly BitacoraService $bitacora,
        private readonly VisitaService $visitas,
    ) {}

    public function handle(Request $peticion, Closure $next): Response
    {
        $respuesta = $next($peticion);

        // Se registra después de responder: lo que se audita es el resultado real.
        $this->sinRomper(function () use ($peticion, $respuesta) {
            if ($this->debeContarVisita($peticion, $respuesta)) {
                $this->visitas->registrar($peticion->path());
            }
        });

        $this->sinRomper(function () use ($peticion, $respuesta) {
            if ($this->debeAuditar($peticion)) {
                $this->bitacora->registrar('acceso_recurso', $peticion, [
                    'codigo_respuesta' => $respuesta->getStatusCode(),
                ]);
            }
        });

        return $respuesta;
    }

    private function sinRomper(callable $accion): void
    {
        try {
            $accion();
        } catch (Throwable $e) {
            Log::warning('No se pudo registrar el acceso: '.$e->getMessage());
        }
    }

    /**
     * Solo las páginas que una persona abre y ve: ni la API, ni redirecciones,
     * ni descargas, ni peticiones de fondo.
     */
    private function debeContarVisita(Request $peticion, Response $respuesta): bool
    {
        return $peticion->isMethod('GET')
            && ! $peticion->is('api/*')
            && ! $peticion->ajax()
            && $respuesta->getStatusCode() === 200
            && str_contains((string) $respuesta->headers->get('Content-Type'), 'text/html');
    }

    /** El login y el logout los registra el controlador con su propio evento. */
    private function debeAuditar(Request $peticion): bool
    {
        if ($peticion->is('api/panel/login', 'api/panel/logout', 'login', 'logout')) {
            return false;
        }

        return $peticion->user() !== null;
    }
}
