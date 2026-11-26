<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use App\Models\Permiso;
use App\Models\RolModelo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class RbacMenuSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'administrador' => ['Administrador', 'Gestión integral del catálogo, planificación, matrículas, reportes y seguridad'],
            'docente' => ['Docente', 'Seguimiento de grupos asignados, estudiantes y calendario'],
            'estudiante' => ['Estudiante', 'Consulta de cursos, horarios, comprobantes y pagos'],
        ];

        $permisos = [
            'panel.ver' => ['Ver panel de inicio', 'general'],
            'catalogo.ver' => ['Consultar catálogo público', 'catalogo'],
            'catalogo.gestionar' => ['Gestionar programas y módulos', 'catalogo'],
            'planificacion.gestionar' => ['Planificar ediciones y grupos', 'planificacion'],
            'aulas.gestionar' => ['Gestionar aulas', 'planificacion'],
            'matriculas.gestionar' => ['Gestionar matrículas y comprobantes', 'matriculas'],
            'reportes.ver' => ['Consultar y descargar reportes', 'reportes'],
            'estadisticas.ver' => ['Consultar estadísticas analíticas', 'reportes'],
            'usuarios.gestionar' => ['Gestionar usuarios', 'seguridad'],
            'clientes_api.gestionar' => ['Gestionar clientes API CRM', 'seguridad'],
            'bitacora.ver' => ['Consultar bitácora de auditoría', 'seguridad'],
            'pagos.propios' => ['Gestionar métodos y pagos propios', 'pagos'],
            'pagos.gestionar' => ['Administrar cobros y transacciones', 'pagos'],
            'docencia.ver' => ['Consultar grupos como docente', 'docencia'],
            'cursos_propios.ver' => ['Consultar cursos propios', 'estudiantes'],
            'calendario.ver' => ['Consultar calendario de clases', 'calendario'],
        ];

        $matriz = [
            'administrador' => [
                'panel.ver',
                'catalogo.ver',
                'catalogo.gestionar',
                'planificacion.gestionar',
                'aulas.gestionar',
                'matriculas.gestionar',
                'reportes.ver',
                'estadisticas.ver',
                'usuarios.gestionar',
                'clientes_api.gestionar',
                'bitacora.ver',
                'pagos.propios',
                'pagos.gestionar',
            ],
            'docente' => [
                'panel.ver',
                'catalogo.ver',
                'docencia.ver',
                'calendario.ver',
            ],
            'estudiante' => [
                'panel.ver',
                'catalogo.ver',
                'cursos_propios.ver',
                'calendario.ver',
                'pagos.propios',
            ],
        ];

        $permisoModelos = [];
        foreach ($permisos as $clave => [$nombre, $modulo]) {
            $permisoModelos[$clave] = Permiso::updateOrCreate(
                ['clave' => $clave],
                ['nombre' => $nombre, 'modulo' => $modulo],
            );
        }

        foreach ($roles as $clave => [$nombre, $descripcion]) {
            $rol = RolModelo::updateOrCreate(
                ['clave' => $clave],
                ['nombre' => $nombre, 'descripcion' => $descripcion],
            );

            $ids = array_map(fn (string $p) => $permisoModelos[$p]->id, $matriz[$clave]);
            $rol->permisos()->sync($ids);
        }

        $itemsRaiz = [
            ['etiqueta' => 'Inicio', 'ruta' => '/', 'icono' => 'inicio', 'permiso_clave' => 'panel.ver', 'orden' => 1],
            ['etiqueta' => 'Catálogo', 'ruta' => '/catalogo', 'icono' => 'catalogo', 'permiso_clave' => 'docencia.ver', 'orden' => 2],
            ['etiqueta' => 'Catálogo', 'ruta' => '/catalogo', 'icono' => 'catalogo', 'permiso_clave' => 'cursos_propios.ver', 'orden' => 3],
        ];

        foreach ($itemsRaiz as $datos) {
            MenuItem::updateOrCreate(
                ['parent_id' => null, 'etiqueta' => $datos['etiqueta'], 'permiso_clave' => $datos['permiso_clave']],
                $datos + ['activo' => true],
            );
        }

        $submenus = [
            [
                'etiqueta' => 'Programas',
                'icono' => 'programa',
                'permiso_clave' => 'catalogo.gestionar',
                'orden' => 4,
                'hijos' => [
                    ['etiqueta' => 'Catálogo', 'ruta' => '/catalogo', 'icono' => 'catalogo', 'permiso_clave' => 'catalogo.ver', 'orden' => 1],
                    ['etiqueta' => 'Módulos', 'ruta' => '/modulos', 'icono' => 'modulos', 'permiso_clave' => 'catalogo.gestionar', 'orden' => 2],
                    ['etiqueta' => 'Programas', 'ruta' => '/programas', 'icono' => 'programa', 'permiso_clave' => 'catalogo.gestionar', 'orden' => 3],
                    ['etiqueta' => 'Ediciones', 'ruta' => '/cohortes', 'icono' => 'calendario', 'permiso_clave' => 'planificacion.gestionar', 'orden' => 4],
                    ['etiqueta' => 'Grupos', 'ruta' => '/grupos', 'icono' => 'usuarios', 'permiso_clave' => 'planificacion.gestionar', 'orden' => 5],
                    ['etiqueta' => 'Aulas', 'ruta' => '/aulas', 'icono' => 'inicio', 'permiso_clave' => 'aulas.gestionar', 'orden' => 6],
                    ['etiqueta' => 'Matrículas', 'ruta' => '/matriculas', 'icono' => 'matricula', 'permiso_clave' => 'matriculas.gestionar', 'orden' => 7],
                ],
            ],
            [
                'etiqueta' => 'Docencia',
                'icono' => 'usuarios',
                'permiso_clave' => 'docencia.ver',
                'orden' => 5,
                'hijos' => [
                    ['etiqueta' => 'Mis grupos', 'ruta' => '/mis-grupos', 'icono' => 'usuarios', 'permiso_clave' => 'docencia.ver', 'orden' => 1],
                    ['etiqueta' => 'Calendario', 'ruta' => '/calendario', 'icono' => 'calendario', 'permiso_clave' => 'calendario.ver', 'orden' => 2],
                ],
            ],
            [
                'etiqueta' => 'Mis Estudios',
                'icono' => 'video',
                'permiso_clave' => 'cursos_propios.ver',
                'orden' => 6,
                'hijos' => [
                    ['etiqueta' => 'Mis cursos', 'ruta' => '/mis-cursos', 'icono' => 'video', 'permiso_clave' => 'cursos_propios.ver', 'orden' => 1],
                    ['etiqueta' => 'Calendario', 'ruta' => '/calendario', 'icono' => 'calendario', 'permiso_clave' => 'calendario.ver', 'orden' => 2],
                    ['etiqueta' => 'Mis pagos', 'ruta' => '/pagos', 'icono' => 'matricula', 'permiso_clave' => 'pagos.propios', 'orden' => 3],
                ],
            ],
        ];

        foreach ($submenus as $grupo) {
            $padre = MenuItem::updateOrCreate(
                ['parent_id' => null, 'etiqueta' => $grupo['etiqueta'], 'permiso_clave' => $grupo['permiso_clave']],
                [
                    'ruta' => null,
                    'icono' => $grupo['icono'],
                    'orden' => $grupo['orden'],
                    'activo' => true,
                ],
            );

            foreach ($grupo['hijos'] as $hijo) {
                MenuItem::updateOrCreate(
                    ['parent_id' => $padre->id, 'ruta' => $hijo['ruta']],
                    $hijo + ['activo' => true],
                );
            }
        }

        $directosFinal = [
            ['etiqueta' => 'Reportes', 'ruta' => '/reportes', 'icono' => 'reportes', 'permiso_clave' => 'reportes.ver', 'orden' => 7],
            ['etiqueta' => 'Estadísticas', 'ruta' => '/estadisticas', 'icono' => 'reportes', 'permiso_clave' => 'estadisticas.ver', 'orden' => 8],
        ];

        foreach ($directosFinal as $datos) {
            MenuItem::updateOrCreate(
                ['parent_id' => null, 'etiqueta' => $datos['etiqueta'], 'permiso_clave' => $datos['permiso_clave']],
                $datos + ['activo' => true],
            );
        }

        $seguridad = MenuItem::updateOrCreate(
            ['parent_id' => null, 'etiqueta' => 'Configuración y Seguridad', 'permiso_clave' => 'usuarios.gestionar'],
            ['ruta' => null, 'icono' => 'candado', 'orden' => 9, 'activo' => true],
        );

        $hijosSeguridad = [
            ['etiqueta' => 'Usuarios', 'ruta' => '/usuarios', 'icono' => 'usuarios', 'permiso_clave' => 'usuarios.gestionar', 'orden' => 1],
            ['etiqueta' => 'Acceso API', 'ruta' => '/clientes-api', 'icono' => 'candado', 'permiso_clave' => 'clientes_api.gestionar', 'orden' => 2],
            ['etiqueta' => 'Bitácora', 'ruta' => '/bitacora', 'icono' => 'candado', 'permiso_clave' => 'bitacora.ver', 'orden' => 3],
            ['etiqueta' => 'Métodos de pago', 'ruta' => '/pagos', 'icono' => 'matricula', 'permiso_clave' => 'pagos.gestionar', 'orden' => 4],
        ];

        foreach ($hijosSeguridad as $hijo) {
            MenuItem::updateOrCreate(
                ['parent_id' => $seguridad->id, 'ruta' => $hijo['ruta']],
                $hijo + ['activo' => true],
            );
        }

        foreach (array_keys($roles) as $clave) {
            Cache::forget("rbac.menu.{$clave}");
            Cache::forget("rbac.permisos.{$clave}");
        }
    }
}
