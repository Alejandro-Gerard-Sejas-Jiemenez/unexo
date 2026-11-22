<?php

namespace App\Services;

use App\Models\Bitacora;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

/**
 * Escritura de la bitácora de auditoría. Un solo punto de entrada para que
 * ninguna ruta quede fuera del registro y el formato sea siempre el mismo.
 */
class BitacoraService
{
    public function registrar(string $evento, Request $peticion, array $extra = []): Bitacora
    {
        return Bitacora::create([
            'evento' => $evento,
            // Solo un usuario de negocio tiene fila en `users`. Quien entra con un
            // token de cliente de la API no es uno, y ahí user_id queda nulo.
            'user_id' => $extra['user_id'] ?? $this->idDeUsuario($peticion),
            'identificador' => $extra['identificador'] ?? null,
            'recurso' => $extra['recurso'] ?? '/'.ltrim($peticion->path(), '/'),
            'metodo_http' => $peticion->method(),
            'codigo_respuesta' => $extra['codigo_respuesta'] ?? null,
            'ip' => $peticion->ip(),
            'user_agent' => substr((string) $peticion->userAgent(), 0, 1000),
        ]);
    }

    private function idDeUsuario(Request $peticion): ?string
    {
        $autenticado = $peticion->user();

        return $autenticado instanceof User ? $autenticado->id : null;
    }

    public function loginExitoso(Request $peticion, string $userId): void
    {
        $this->registrar('login_exitoso', $peticion, ['user_id' => $userId, 'codigo_respuesta' => 200]);
    }

    /** El intento fallido guarda el correo probado, pero nunca la contraseña. */
    public function loginFallido(Request $peticion, string $identificador, string $motivo): void
    {
        $this->registrar('login_fallido', $peticion, [
            'user_id' => null,
            'identificador' => substr($identificador, 0, 255),
            'recurso' => $motivo,
            'codigo_respuesta' => 422,
        ]);
    }

    public function logout(Request $peticion, ?string $userId): void
    {
        $this->registrar('logout', $peticion, ['user_id' => $userId, 'codigo_respuesta' => 200]);
    }

    /**
     * Consulta de la bitácora para la pantalla de auditoría.
     *
     * @param  array{evento?: ?string, q?: ?string, desde?: ?string, hasta?: ?string}  $filtros
     */
    public function listar(array $filtros): LengthAwarePaginator
    {
        $evento = $filtros['evento'] ?? null;
        $busqueda = trim((string) ($filtros['q'] ?? ''));

        return Bitacora::query()
            ->with('usuario:id,nombres,apellidos,email')
            ->when($evento, fn ($q) => $q->where('evento', $evento))
            ->when($filtros['desde'] ?? null, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filtros['hasta'] ?? null, fn ($q, $h) => $q->whereDate('created_at', '<=', $h))
            ->when($busqueda !== '', fn ($q) => $q->where(fn ($b) => $b
                ->where('recurso', 'like', "%{$busqueda}%")
                ->orWhere('ip', 'like', "%{$busqueda}%")
                ->orWhere('identificador', 'like', "%{$busqueda}%")))
            ->orderByDesc('created_at')
            ->paginate(config('limites.por_pagina'));
    }
}
