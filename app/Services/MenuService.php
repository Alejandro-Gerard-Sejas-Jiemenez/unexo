<?php

namespace App\Services;

use App\Enums\Rol;
use App\Models\User;

class MenuService
{
    /**
     * Devuelve los elementos de navegación correspondientes al rol del usuario.
     */
    public function paraUsuario(User $usuario): array
    {
        return match ($usuario->rol) {
            Rol::Administrador => $this->menuAdministrador(),
            Rol::Docente => $this->menuDocente(),
            Rol::Estudiante => $this->menuEstudiante(),
        };
    }

    /**
     * El mismo menú, normalizado para la SPA: una sola definición sirve al panel
     * Blade (que usa 'url') y a React (que usa 'ruta').
     */
    public function paraSpa(User $usuario): array
    {
        return array_map(function (array $entrada): array {
            if (($entrada['tipo'] ?? null) === 'submenu') {
                return [
                    'tipo' => 'submenu',
                    'etiqueta' => $entrada['etiqueta'],
                    'icono' => $entrada['icono'] ?? null,
                    'items' => array_map(fn (array $item) => [
                        'etiqueta' => $item[0],
                        'ruta' => $this->rutaSpa($item[2]),
                        'icono' => $item[3] ?? null,
                    ], $entrada['items']),
                ];
            }

            return [
                'tipo' => 'enlace',
                'etiqueta' => $entrada['etiqueta'],
                'ruta' => $this->rutaSpa($entrada['patron']),
                'icono' => $entrada['icono'] ?? null,
            ];
        }, $this->paraUsuario($usuario));
    }

    /** Convierte el patrón de ruta del servidor en la ruta del router de React. */
    private function rutaSpa(string $patron): string
    {
        $base = explode('.', $patron)[0];

        return $base === 'panel' ? '/app' : '/app/'.$base;
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
                // Vive solo en la SPA, por eso la URL es la del cliente y no una ruta Blade.
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
