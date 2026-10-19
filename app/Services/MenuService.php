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
                'tipo' => 'submenu',
                'etiqueta' => 'Configuración y Seguridad',
                'icono' => 'candado',
                'items' => [
                    ['Usuarios', route('usuarios.index'), 'usuarios.*', 'usuarios'],
                    ['Acceso API', route('clientes-api.index'), 'clientes-api.*', 'candado'],
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
                ],
            ],
        ];
    }
}
