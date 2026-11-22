<?php

namespace App\Services;

use App\Enums\Rol;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\User;

/**
 * Búsqueda global del encabezado. Los resultados se filtran en el servidor
 * según el rol de quien busca: nunca se devuelve algo que el usuario no podría abrir.
 */
class BuscadorService
{
    private const POR_GRUPO = 5;

    public function buscar(string $termino, User $usuario): array
    {
        $termino = trim($termino);

        if (mb_strlen($termino) < 2) {
            return ['secciones' => [], 'resultados' => []];
        }

        $como = "%{$termino}%";
        $esAdmin = $usuario->rol === Rol::Administrador;

        $resultados = [
            $this->bloque('Programas', Programa::query()
                ->where(fn ($q) => $q->where('nombre', 'like', $como)->orWhere('codigo', 'like', $como))
                ->limit(self::POR_GRUPO)
                ->get()
                ->map(fn (Programa $p) => [
                    'titulo' => $p->nombre,
                    'detalle' => "Código {$p->codigo} · versión {$p->version}",
                    'ruta' => $esAdmin ? '/app/programas' : '/app/catalogo',
                ])),

            $this->bloque('Módulos', Modulo::query()
                ->where(fn ($q) => $q->where('nombre', 'like', $como)->orWhere('codigo', 'like', $como))
                ->limit(self::POR_GRUPO)
                ->get()
                ->map(fn (Modulo $m) => [
                    'titulo' => $m->nombre,
                    'detalle' => "Código {$m->codigo}",
                    'ruta' => $esAdmin ? '/app/modulos' : '/app/catalogo',
                ])),

            $this->bloque('Ediciones', Cohorte::query()
                ->where('nombre', 'like', $como)
                ->limit(self::POR_GRUPO)
                ->get()
                ->map(fn (Cohorte $c) => [
                    'titulo' => $c->nombre,
                    'detalle' => 'Edición programada',
                    'ruta' => '/app/cohortes',
                ])),

            $this->bloque('Grupos', Grupo::query()
                ->when(! $esAdmin, fn ($q) => $q->where('docente_id', $usuario->id))
                ->where('nombre', 'like', $como)
                ->limit(self::POR_GRUPO)
                ->get()
                ->map(fn (Grupo $g) => [
                    'titulo' => $g->nombre,
                    'detalle' => 'Grupo '.$g->modalidad,
                    'ruta' => $esAdmin ? '/app/grupos' : '/app/mis-grupos',
                ])),
        ];

        // Las personas solo las busca quien administra usuarios.
        if ($esAdmin) {
            $resultados[] = $this->bloque('Usuarios', User::query()
                ->where(fn ($q) => $q
                    ->where('nombres', 'like', $como)
                    ->orWhere('apellidos', 'like', $como)
                    ->orWhere('email', 'like', $como))
                ->limit(self::POR_GRUPO)
                ->get()
                ->map(fn (User $u) => [
                    'titulo' => $u->nombre_completo,
                    'detalle' => $u->rol->label().' · '.$u->email,
                    'ruta' => "/app/usuarios/{$u->id}",
                ]));
        }

        return [
            'secciones' => $this->seccionesQueCoinciden($termino, $usuario),
            'resultados' => array_values(array_filter($resultados, fn ($b) => $b['items'] !== [])),
        ];
    }

    private function bloque(string $titulo, mixed $items): array
    {
        return ['titulo' => $titulo, 'items' => $items->values()->all()];
    }

    /** Accesos directos a las secciones del menú cuyo nombre coincide. */
    private function seccionesQueCoinciden(string $termino, User $usuario): array
    {
        $menu = app(MenuService::class)->paraSpa($usuario);
        $planas = [];

        foreach ($menu as $entrada) {
            if (($entrada['tipo'] ?? null) === 'submenu') {
                foreach ($entrada['items'] as $item) {
                    $planas[] = $item;
                }

                continue;
            }

            $planas[] = $entrada;
        }

        return array_values(array_filter(
            $planas,
            fn (array $i) => mb_stripos($i['etiqueta'], $termino) !== false,
        ));
    }
}
