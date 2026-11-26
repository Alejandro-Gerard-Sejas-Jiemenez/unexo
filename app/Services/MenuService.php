<?php

namespace App\Services;

use App\Enums\Rol;
use App\Models\MenuItem;
use App\Models\RolModelo;
use App\Models\User;
use Database\Seeders\RbacMenuSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class MenuService
{
    public function paraUsuario(User $usuario): array
    {
        return match ($usuario->rol) {
            Rol::Administrador => $this->menuAdministrador(),
            Rol::Docente => $this->menuDocente(),
            Rol::Estudiante => $this->menuEstudiante(),
        };
    }

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

    private function menuAdministrador(): array
    {
        return [
            [
                'tipo' => 'enlace',
                'etiqueta' => 'Inicio',
                'url' => route('panel'),
                'patron' => 'panel',
                'icono' => 'inicio',
            ],
            [
                'tipo' => 'submenu',
                'etiqueta' => 'Programas',
                'icono' => 'programa',
                'items' => [
                    ['Catálogo', route('catalogo'), 'catalogo', 'catalogo'],
                    ['Módulos', route('modulos.index'), 'modulos.*', 'modulos'],
                    ['Programas', route('programas.index'), 'programas.*', 'programa'],
                    ['Ediciones', route('cohortes.index'), 'cohortes.*', 'calendario'],
                    ['Grupos', route('grupos.index'), 'grupos.*', 'usuarios'],
                    ['Aulas', route('aulas.index'), 'aulas.*', 'inicio'],
                    ['Matrículas', route('matriculas.index'), 'matriculas.*', 'matricula'],
                ],
            ],
            [
                'tipo' => 'enlace',
                'etiqueta' => 'Reportes',
                'url' => route('reportes.matriculas'),
                'patron' => 'reportes.*',
                'icono' => 'reportes',
            ],
            [
                'tipo' => 'enlace',
                'etiqueta' => 'Estadísticas',
                'url' => url('/app/estadisticas'),
                'patron' => 'estadisticas',
                'icono' => 'reportes',
            ],
            [
                'tipo' => 'submenu',
                'etiqueta' => 'Configuración y Seguridad',
                'icono' => 'candado',
                'items' => [
                    ['Usuarios', route('usuarios.index'), 'usuarios.*', 'usuarios'],
                    ['Acceso API', route('clientes-api.index'), 'clientes-api.*', 'candado'],
                    ['Bitácora', url('/app/bitacora'), 'bitacora', 'candado'],
                    ['Métodos de pago', url('/app/pagos'), 'pagos', 'matricula'],
                ],
            ],
        ];
    }

    private function menuDocente(): array
    {
        return [
            [
                'tipo' => 'enlace',
                'etiqueta' => 'Inicio',
                'url' => route('panel'),
                'patron' => 'panel',
                'icono' => 'inicio',
            ],
            [
                'tipo' => 'enlace',
                'etiqueta' => 'Catálogo',
                'url' => route('catalogo'),
                'patron' => 'catalogo',
                'icono' => 'catalogo',
            ],
            [
                'tipo' => 'submenu',
                'etiqueta' => 'Docencia',
                'icono' => 'usuarios',
                'items' => [
                    ['Mis grupos', route('mis-grupos.index'), 'mis-grupos.*', 'usuarios'],
                    ['Calendario', route('calendario'), 'calendario', 'calendario'],
                ],
            ],
        ];
    }

    private function menuEstudiante(): array
    {
        return [
            [
                'tipo' => 'enlace',
                'etiqueta' => 'Inicio',
                'url' => route('panel'),
                'patron' => 'panel',
                'icono' => 'inicio',
            ],
            [
                'tipo' => 'enlace',
                'etiqueta' => 'Catálogo',
                'url' => route('catalogo'),
                'patron' => 'catalogo',
                'icono' => 'catalogo',
            ],
            [
                'tipo' => 'submenu',
                'etiqueta' => 'Mis Estudios',
                'icono' => 'video',
                'items' => [
                    ['Mis cursos', route('mis-cursos'), 'mis-cursos', 'video'],
                    ['Calendario', route('calendario'), 'calendario', 'calendario'],
                    ['Mis pagos', url('/app/pagos'), 'pagos', 'matricula'],
                ],
            ],
        ];
    }
}
