<?php

namespace App\Services;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Consulta de usuarios con filtros. La usan el panel Blade y la API del panel,
 * para que los dos listados filtren y ordenen igual.
 */
class UsuarioService
{
    /**
     * @param  array{rol?: ?string, estado?: ?string, q?: ?string}  $filtros
     */
    public function listar(array $filtros): LengthAwarePaginator
    {
        $rol = Rol::tryFrom((string) ($filtros['rol'] ?? ''));
        $soloInactivos = ($filtros['estado'] ?? null) === 'inactivo';
        $busqueda = trim((string) ($filtros['q'] ?? ''));

        return User::query()
            ->when($rol, fn ($q, $r) => $q->where('rol', $r->value))
            ->when($soloInactivos, fn ($q) => $q->where('estado', 'inactivo'))
            ->when($busqueda !== '', fn ($q) => $q->where(fn ($b) => $b
                ->where('nombres', 'like', "%{$busqueda}%")
                ->orWhere('apellidos', 'like', "%{$busqueda}%")
                ->orWhere('email', 'like', "%{$busqueda}%")
                ->orWhere('correo_institucional', 'like', "%{$busqueda}%")))
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(config('limites.por_pagina'));
    }

    /**
     * Alterna activo/inactivo. Devuelve el motivo del rechazo, o null si se pudo cambiar.
     */
    public function alternarEstado(User $usuario, User $autor): ?string
    {
        if ($usuario->is($autor)) {
            return 'No puedes desactivar tu propia cuenta.';
        }

        $usuario->update(['estado' => $usuario->estaActivo() ? 'inactivo' : 'activo']);

        return null;
    }
}
