<?php

namespace App\Services;

use App\Enums\EstadoComercial;
use App\Enums\Rol;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ResumenCatalogoService
{
    public function ofertaVisibleParaCrm(): array
    {
        return [
            'programas' => Programa::abierto()->count(),
            'modulos' => Modulo::visibleParaCrm()->count(),
        ];
    }

    public function avisos(): array
    {
        return $this->construirAvisos(false);
    }

    public function avisosSpa(): array
    {
        return $this->construirAvisos(true);
    }

    private function construirAvisos(bool $paraSpa): array
    {
        $borrador = EstadoComercial::Borrador->value;
        $programas = Programa::query()
            ->selectRaw(
                'SUM(CASE WHEN estado_comercial = ? THEN 1 ELSE 0 END) as borrador_total',
                [$borrador],
            )
            ->first();

        $avisos = [
            [
                'cantidad' => Programa::abiertoSinModulosAbiertos()->count(),
                'singular' => 'programa abierto sin módulos abiertos: el CRM lo ve vacío',
                'plural' => 'programas abiertos sin módulos abiertos: el CRM los ve vacíos',
                'ruta' => $paraSpa
                    ? '/programas?atencion=sin-modulos-abiertos'
                    : route('programas.index', ['atencion' => 'sin-modulos-abiertos']),
            ],
            [
                'cantidad' => (int) ($programas->borrador_total ?? 0),
                'singular' => 'programa en borrador, aún no visible para el CRM',
                'plural' => 'programas en borrador, aún no visibles para el CRM',
                'ruta' => $paraSpa
                    ? "/programas?estado={$borrador}"
                    : route('programas.index', ['estado' => $borrador]),
            ],
            [
                'cantidad' => User::where('estado', 'inactivo')->count(),
                'singular' => 'usuario inactivo, no puede ingresar',
                'plural' => 'usuarios inactivos, no pueden ingresar',
                'ruta' => $paraSpa
                    ? '/usuarios?estado=inactivo'
                    : route('usuarios.index', ['estado' => 'inactivo']),
            ],
        ];

        return array_values(array_filter($avisos, fn (array $aviso) => $aviso['cantidad'] > 0));
    }

    public function totales(): array
    {
        $usuarios = User::query()
            ->select('rol', DB::raw('count(*) as total'))
            ->groupBy('rol')
            ->pluck('total', 'rol');

        return [
            'usuarios' => (int) $usuarios->sum(),
            'docentes' => (int) ($usuarios[Rol::Docente->value] ?? 0),
            'estudiantes' => (int) ($usuarios[Rol::Estudiante->value] ?? 0),
            'programas' => Programa::count(),
            'modulos' => Modulo::count(),
        ];
    }
}
