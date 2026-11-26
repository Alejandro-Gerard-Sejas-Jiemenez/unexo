<?php

namespace App\Services;

use App\Enums\EstadoMatricula;
use App\Enums\Rol;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\User;
use Illuminate\Support\Str;

class BuscadorService
{
    private const POR_GRUPO = 5;

    public function buscar(string $termino, User $usuario): array
    {
        $termino = trim($termino);

        if (mb_strlen($termino) < 2) {
            return ['secciones' => [], 'resultados' => []];
        }

        $terminoNorm = $this->normalizar($termino);
        if (mb_strlen($terminoNorm) < 2) {
            return ['secciones' => [], 'resultados' => []];
        }

        $tokens = array_values(array_filter(explode(' ', $terminoNorm), fn (string $t) => $t !== ''));
        $esAdmin = $usuario->rol === Rol::Administrador;
        $esDocente = $usuario->rol === Rol::Docente;

        $programas = $this->rankear(
            Programa::query()
                ->select(['id', 'nombre', 'codigo', 'version'])
                ->when(! $esAdmin, fn ($q) => $q->visibleParaCrm())
                ->get(),
            fn (Programa $p) => [$p->nombre, $p->codigo],
            $terminoNorm,
            $tokens,
        )->map(fn (Programa $p) => [
            'titulo' => $p->nombre,
            'detalle' => "Código {$p->codigo} · versión {$p->version}",
            'ruta' => $esAdmin ? "/programas/{$p->id}" : '/catalogo',
        ]);

        $modulos = $this->rankear(
            Modulo::query()
                ->select(['id', 'nombre', 'codigo'])
                ->when(! $esAdmin, fn ($q) => $q->visibleParaCrm())
                ->get(),
            fn (Modulo $m) => [$m->nombre, $m->codigo],
            $terminoNorm,
            $tokens,
        )->map(fn (Modulo $m) => [
            'titulo' => $m->nombre,
            'detalle' => "Código {$m->codigo}",
            'ruta' => $esAdmin ? "/modulos/{$m->id}" : '/catalogo',
        ]);

        $resultados = [
            $this->bloque('Programas', $programas),
            $this->bloque('Módulos', $modulos),
        ];

        if ($esAdmin) {
            $cohortes = $this->rankear(
                Cohorte::query()->select(['id', 'nombre'])->get(),
                fn (Cohorte $c) => [$c->nombre],
                $terminoNorm,
                $tokens,
            )->map(fn (Cohorte $c) => [
                'titulo' => $c->nombre,
                'detalle' => 'Edición programada',
                'ruta' => "/cohortes/{$c->id}",
            ]);

            $resultados[] = $this->bloque('Ediciones', $cohortes);
        }

        $gruposQuery = Grupo::query()
            ->select(['id', 'nombre', 'modalidad', 'modulo_id'])
            ->with(['modulo:id,nombre,codigo'])
            ->when($esDocente, fn ($q) => $q->where('docente_id', $usuario->id))
            ->when(! $esAdmin && ! $esDocente, fn ($q) => $q->whereHas(
                'inscripciones.matricula',
                fn ($m) => $m->where('estudiante_id', $usuario->id)
                    ->whereIn('estado', array_map(fn (EstadoMatricula $e) => $e->value, EstadoMatricula::queCuentanComoInscritos())),
            ))
            ->get();

        $grupos = $this->rankear(
            $gruposQuery,
            fn (Grupo $g) => [$g->nombre, $g->modulo?->nombre ?? '', $g->modulo?->codigo ?? ''],
            $terminoNorm,
            $tokens,
        )->map(fn (Grupo $g) => [
            'titulo' => $g->nombre,
            'detalle' => ($g->modulo?->nombre ? $g->modulo->nombre.' · ' : '').'Grupo '.$g->modalidad->label(),
            'ruta' => match (true) {
                $esAdmin => "/grupos/{$g->id}/ver",
                $esDocente => "/mis-grupos/{$g->id}",
                default => "/mis-cursos/{$g->id}",
            },
        ]);

        $resultados[] = $this->bloque('Grupos', $grupos);

        if ($esAdmin) {
            $usuarios = $this->rankear(
                User::query()->select(['id', 'nombres', 'apellidos', 'email', 'rol'])->get(),
                fn (User $u) => [$u->nombre_completo, $u->email],
                $terminoNorm,
                $tokens,
            )->map(fn (User $u) => [
                'titulo' => $u->nombre_completo,
                'detalle' => $u->rol->label().' · '.$u->email,
                'ruta' => "/usuarios/{$u->id}",
            ]);

            $resultados[] = $this->bloque('Usuarios', $usuarios);
        }

        return [
            'secciones' => $this->seccionesQueCoinciden($terminoNorm, $tokens, $usuario),
            'resultados' => array_values(array_filter($resultados, fn ($b) => $b['items'] !== [])),
        ];
    }

    private function bloque(string $titulo, mixed $items): array
    {
        return ['titulo' => $titulo, 'items' => $items->values()->all()];
    }

    private function seccionesQueCoinciden(string $terminoNorm, array $tokens, User $usuario): array
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

        $conPuntaje = [];
        foreach ($planas as $item) {
            $p = $this->puntajeCoincidencia([$item['etiqueta'] ?? ''], $terminoNorm, $tokens);
            if ($p > 0) {
                $conPuntaje[] = ['puntaje' => $p, 'item' => $item];
            }
        }

        usort($conPuntaje, fn (array $a, array $b) => $b['puntaje'] <=> $a['puntaje']);

        return array_map(fn (array $f) => $f['item'], array_slice($conPuntaje, 0, self::POR_GRUPO));
    }

    private function rankear($coleccion, callable $extractor, string $terminoNorm, array $tokens)
    {
        $evaluados = [];

        foreach ($coleccion as $registro) {
            $puntaje = $this->puntajeCoincidencia($extractor($registro), $terminoNorm, $tokens);
            if ($puntaje > 0) {
                $evaluados[] = ['puntaje' => $puntaje, 'registro' => $registro];
            }
        }

        usort($evaluados, fn (array $a, array $b) => $b['puntaje'] <=> $a['puntaje']);

        return collect(array_slice($evaluados, 0, self::POR_GRUPO))
            ->pluck('registro');
    }

    private function normalizar(string $texto): string
    {
        $ascii = Str::lower(Str::ascii($texto));
        $limpio = preg_replace('/[^a-z0-9]+/', ' ', $ascii) ?? $ascii;

        return trim($limpio);
    }

    /**
     * Evalúa coincidencia exacta, sin tildes, por tokens o difusa (errores de tipeo).
     */
    private function puntajeCoincidencia(array $campos, string $terminoNorm, array $tokens): int
    {
        $combinado = $this->normalizar(implode(' ', $campos));
        if ($combinado === '') {
            return 0;
        }

        if (str_starts_with($combinado, $terminoNorm)) {
            return 120;
        }

        if (str_contains($combinado, $terminoNorm)) {
            return 100;
        }

        $palabrasObjetivo = array_values(array_filter(explode(' ', $combinado), fn (string $w) => $w !== ''));
        if ($palabrasObjetivo === [] || $tokens === []) {
            return 0;
        }

        $sumaTokens = 0;

        foreach ($tokens as $token) {
            $mejorToken = 0;
            $len = strlen($token);

            if (str_contains($combinado, $token)) {
                $mejorToken = 85;
            } else {
                $tolerancia = match (true) {
                    $len >= 7 => 2,
                    $len >= 4 => 1,
                    default => 0,
                };

                foreach ($palabrasObjetivo as $palabra) {
                    if ($len >= 2 && str_starts_with($palabra, $token)) {
                        $mejorToken = max($mejorToken, 78);
                        continue;
                    }

                    if ($tolerancia > 0 && abs(strlen($palabra) - $len) <= $tolerancia) {
                        $distancia = levenshtein($token, $palabra);
                        if ($distancia <= $tolerancia) {
                            $mejorToken = max($mejorToken, 72 - ($distancia * 12));
                        }
                    }
                }
            }

            if ($mejorToken === 0) {
                return 0;
            }

            $sumaTokens += $mejorToken;
        }

        return (int) round($sumaTokens / count($tokens));
    }
}
