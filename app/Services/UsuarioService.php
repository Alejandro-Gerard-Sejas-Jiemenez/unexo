<?php

namespace App\Services;

use App\Support\TamanoPagina;
use App\Enums\Rol;
use App\Models\User;
use App\Support\ConsultaTexto;
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
            ->when($busqueda !== '', fn ($q) => $q->where(function ($b) use ($busqueda) {
                ConsultaTexto::dondeContiene($b, 'nombres', $busqueda);
                ConsultaTexto::dondeContiene($b, 'apellidos', $busqueda, true);
                ConsultaTexto::dondeContiene($b, 'email', $busqueda, true);
                ConsultaTexto::dondeContiene($b, 'correo_institucional', $busqueda, true);
            }))
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(TamanoPagina::elegido());
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
