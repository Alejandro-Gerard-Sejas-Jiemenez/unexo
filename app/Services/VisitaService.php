<?php

namespace App\Services;

use App\Models\VisitaPagina;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Contador de accesos por página, persistido en la base de datos.
 * Lo muestra el pie de todas las vistas del sitio.
 */
class VisitaService
{
    /**
     * Suma una visita a la ruta.
     *
     * Una sola sentencia con ON CONFLICT (la soportan PostgreSQL y SQLite): es
     * atómica, así que dos visitas simultáneas no se pisan, y cuesta siempre una
     * consulta, exista la fila o no.
     */
    public function registrar(string $ruta, ?string $usuarioId = null): void
    {
        $ruta = '/'.trim($ruta, '/');
        $ahora = now();

        DB::insert(
            'insert into visitas_pagina (id, ruta, contador, ultima_visita, ultimo_usuario_id, created_at, updated_at)
             values (?, ?, 1, ?, ?, ?, ?)
             on conflict (ruta) do update set
                contador = visitas_pagina.contador + 1,
                ultima_visita = excluded.ultima_visita,
                ultimo_usuario_id = excluded.ultimo_usuario_id,
                updated_at = excluded.updated_at',
            [(string) Str::uuid(), $ruta, $ahora, $usuarioId, $ahora, $ahora],
        );
    }

    public function totalDelSitio(): int
    {
        return (int) VisitaPagina::sum('contador');
    }

    public function deLaRuta(string $ruta): int
    {
        return (int) VisitaPagina::where('ruta', '/'.trim($ruta, '/'))->value('contador');
    }

    /** @return array<int, array{ruta: string, contador: int}> */
    public function masVisitadas(int $limite = 10): array
    {
        return VisitaPagina::orderByDesc('contador')
            ->limit($limite)
            ->get(['ruta', 'contador'])
            ->map(fn (VisitaPagina $v) => ['ruta' => $v->ruta, 'contador' => $v->contador])
            ->all();
    }
}
