<?php

namespace App\Services;

use App\Models\MenuItem;
use App\Models\RolModelo;
use App\Models\User;
use Database\Seeders\RbacMenuSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class MenuService
{
    /** @return list<string> */
    public function permisosDe(User $usuario): array
    {
        $clave = $usuario->rol->value;
        $this->asegurarCatalogo();

        return Cache::remember("rbac.permisos.{$clave}", 300, function () use ($clave): array {
            $rol = RolModelo::with('permisos:id,clave')->where('clave', $clave)->first();

            return $rol ? $rol->permisos->pluck('clave')->values()->all() : [];
        });
    }

    public function paraSpa(User $usuario): array
    {
        $clave = $usuario->rol->value;
        $permisos = $this->permisosDe($usuario);

        return Cache::remember("rbac.menu.{$clave}", 300, function () use ($permisos): array {
            $raices = MenuItem::query()
                ->whereNull('parent_id')
                ->where('activo', true)
                ->with(['hijos' => fn ($q) => $q->where('activo', true)->orderBy('orden')])
                ->orderBy('orden')
                ->get();

            $salida = [];

            foreach ($raices as $item) {
                if ($item->permiso_clave !== null && ! in_array($item->permiso_clave, $permisos, true)) {
                    continue;
                }

                if ($item->hijos->isNotEmpty()) {
                    $hijos = $item->hijos
                        ->filter(fn (MenuItem $h) => $h->permiso_clave === null || in_array($h->permiso_clave, $permisos, true))
                        ->map(fn (MenuItem $h) => [
                            'etiqueta' => $h->etiqueta,
                            'ruta' => $h->ruta,
                            'icono' => $h->icono,
                        ])
                        ->values()
                        ->all();

                    if ($hijos !== []) {
                        $salida[] = [
                            'tipo' => 'submenu',
                            'etiqueta' => $item->etiqueta,
                            'icono' => $item->icono,
                            'items' => $hijos,
                        ];
                    }

                    continue;
                }

                $salida[] = [
                    'tipo' => 'enlace',
                    'etiqueta' => $item->etiqueta,
                    'ruta' => $item->ruta,
                    'icono' => $item->icono,
                ];
            }

            return $salida;
        });
    }

    private function asegurarCatalogo(): void
    {
        if (Schema::hasTable('roles') && ! RolModelo::query()->exists()) {
            (new RbacMenuSeeder())->run();
        }
    }
}
