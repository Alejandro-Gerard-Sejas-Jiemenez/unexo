<?php

namespace App\Services;

use App\Enums\EstadoComercial;
use App\Enums\Rol;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\User;

class ResumenCatalogoService
{
    public function ofertaVisibleParaCrm(): array
    {
        return [
            'programas' => Programa::abierto()->count(),
            'modulos' => Modulo::visibleParaCrm()->count(),
        ];
    }

    /** Avisos con cantidad mayor a cero, de mayor a menor urgencia; cada uno apunta a su lista ya filtrada. */
    public function avisos(): array
    {
        $avisos = [
            [
                'cantidad' => Programa::abiertoSinModulosAbiertos()->count(),
                'singular' => 'programa abierto sin módulos abiertos: el CRM lo ve vacío',
                'plural' => 'programas abiertos sin módulos abiertos: el CRM los ve vacíos',
                'ruta' => route('programas.index', ['atencion' => 'sin-modulos-abiertos']),
            ],
            [
                'cantidad' => Programa::where('estado_comercial', EstadoComercial::Borrador->value)->count(),
                'singular' => 'programa en borrador, aún no visible para el CRM',
                'plural' => 'programas en borrador, aún no visibles para el CRM',
                'ruta' => route('programas.index', ['estado' => EstadoComercial::Borrador->value]),
            ],
            [
                'cantidad' => User::where('estado', 'inactivo')->count(),
                'singular' => 'usuario inactivo, no puede ingresar',
                'plural' => 'usuarios inactivos, no pueden ingresar',
                'ruta' => route('usuarios.index', ['estado' => 'inactivo']),
            ],
        ];

        return array_values(array_filter($avisos, fn (array $aviso) => $aviso['cantidad'] > 0));
    }

    public function totales(): array
    {
        return [
            'usuarios' => User::count(),
            'docentes' => User::where('rol', Rol::Docente->value)->count(),
            'estudiantes' => User::where('rol', Rol::Estudiante->value)->count(),
            'programas' => Programa::count(),
            'modulos' => Modulo::count(),
        ];
    }
}
