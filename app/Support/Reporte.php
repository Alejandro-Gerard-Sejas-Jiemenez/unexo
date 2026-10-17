<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * Fuente única de un reporte: la pantalla, el PDF y el Excel leen esta misma estructura,
 * así los datos y el orden de las filas coinciden por construcción.
 *
 * - filtros:   [['etiqueta' => string, 'valor' => string]]  ("Todos" cuando no se aplicó)
 * - totales:   [['etiqueta' => string, 'valor' => int]]  (tarjetas de nombre y número, RF-30)
 * - secciones: [['titulo' => string, 'columnas' => [['titulo' => string, 'tipo' => string]],
 *               'filas' => [[valor, ...]], 'vacio' => ?string]]
 *   Los tipos de columna son: texto, entero, porcentaje, fecha. Cada fila sigue el orden de las columnas.
 */
class Reporte
{
    public const ZONA_HORARIA = 'America/La_Paz';

    public const AVISO_SIN_DATOS = 'Sin datos para los filtros aplicados';

    public const SIN_VALOR = '—';

    public function __construct(
        public readonly string $titulo,
        public readonly string $archivo,
        public readonly Carbon $generadoEn,
        public readonly array $filtros,
        public readonly array $totales,
        public readonly array $secciones,
        public readonly bool $sinDatos,
        public readonly bool $horizontal = false,
    ) {}

    public static function ahora(): Carbon
    {
        return Carbon::now(self::ZONA_HORARIA);
    }

    public static function porcentaje(int $cantidad, int $total): float
    {
        return $total === 0 ? 0.0 : round($cantidad * 100 / $total, 1);
    }

    /** Texto para pantalla y PDF. El Excel guarda el valor nativo, sin pasar por aquí. */
    public static function formatear(mixed $valor, string $tipo): string
    {
        if ($valor === null || $valor === '') {
            return self::SIN_VALOR;
        }

        return match ($tipo) {
            'porcentaje' => number_format((float) $valor, 1, ',', '').' %',
            'fecha' => $valor instanceof DateTimeInterface ? $valor->format('d/m/Y') : (string) $valor,
            default => (string) $valor,
        };
    }

    public function fechaGeneracion(): string
    {
        return $this->generadoEn->format('d/m/Y H:i');
    }

    public function nombreArchivo(string $extension): string
    {
        return "reporte-{$this->archivo}-{$this->generadoEn->format('Ymd-Hi')}.{$extension}";
    }
}
