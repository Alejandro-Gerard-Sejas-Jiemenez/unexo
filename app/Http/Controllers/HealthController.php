<?php

namespace App\Http\Controllers;

use App\Services\SaludService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Salud pública para monitores externos: solo "ok" o "falla", sin datos del servidor.
 * El detalle lo ve el administrador en /api/panel/salud.
 */
class HealthController extends Controller
{
    public function __invoke(SaludService $salud): JsonResponse
    {
        $resultado = $salud->diagnosticar();

        if ($resultado['critico_ok']) {
            return response()->json(['estado' => 'ok']);
        }

        $this->registrarFalla($resultado['chequeos']);

        return response()->json(['estado' => 'falla'], 503);
    }

    /** Deja rastro en el log, como máximo una vez por minuto aunque el monitor consulte más. */
    private function registrarFalla(array $chequeos): void
    {
        try {
            if (! Cache::add('salud.falla.registrada', true, 60)) {
                return;
            }
        } catch (Throwable) {
            // Sin caché (por ejemplo, base caída): se registra igual.
        }

        $fallas = collect($chequeos)->where('critico', true)->where('estado', 'falla')->pluck('clave')->implode(', ');
        Log::error("Health: fallan los chequeos críticos ({$fallas}).");
    }
}
