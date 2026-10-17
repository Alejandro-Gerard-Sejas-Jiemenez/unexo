<?php

namespace App\Services;

use App\Enums\EstadoComercial;
use App\Models\Modulo;
use App\Models\Programa;
use App\Support\Reporte;

/** Reporte de solo lectura: catálogo por estado comercial y oferta visible para el CRM. Sin precios. */
class ReporteCatalogoService
{
    public const SITUACION_VACIO = 'El CRM lo ve vacío';

    public const SIN_PROGRAMAS_VACIOS = 'No hay programas abiertos sin módulos abiertos.';

    public function generar(): Reporte
    {
        $generadoEn = Reporte::ahora();

        $programasPorEstado = Programa::query()->toBase()->selectRaw('estado_comercial as clave, count(*) as cantidad')->groupBy('estado_comercial')->pluck('cantidad', 'clave');
        $modulosPorEstado = Modulo::query()->toBase()->selectRaw('estado_comercial as clave, count(*) as cantidad')->groupBy('estado_comercial')->pluck('cantidad', 'clave');

        $totalProgramas = (int) $programasPorEstado->sum();
        $totalModulos = (int) $modulosPorEstado->sum();
        $sinDatos = $totalProgramas === 0 && $totalModulos === 0;

        $programasVisibles = Programa::visibleParaCrm()->count();
        $modulosVisibles = Modulo::visibleParaCrm()->count();

        return new Reporte(
            titulo: 'Reporte de catálogo y oferta',
            archivo: 'catalogo-oferta',
            generadoEn: $generadoEn,
            filtros: [['etiqueta' => 'Filtros', 'valor' => 'Sin filtros']],
            totales: [
                ['etiqueta' => 'Programas en la oferta visible', 'valor' => $programasVisibles],
                ['etiqueta' => 'Módulos en la oferta visible', 'valor' => $modulosVisibles],
            ],
            secciones: $sinDatos ? [] : [
                [
                    'titulo' => 'Programas y módulos por estado comercial',
                    'columnas' => [
                        ['titulo' => 'Estado', 'tipo' => 'texto'],
                        ['titulo' => 'Programas', 'tipo' => 'entero'],
                        ['titulo' => 'Módulos', 'tipo' => 'entero'],
                    ],
                    'filas' => array_map(fn (EstadoComercial $estado) => [
                        $estado->label(),
                        (int) ($programasPorEstado[$estado->value] ?? 0),
                        (int) ($modulosPorEstado[$estado->value] ?? 0),
                    ], EstadoComercial::cases()),
                ],
                [
                    'titulo' => 'Programas abiertos sin módulos abiertos (el CRM los ve vacíos)',
                    'columnas' => [
                        ['titulo' => 'Código', 'tipo' => 'texto'],
                        ['titulo' => 'Programa', 'tipo' => 'texto'],
                        ['titulo' => 'Situación', 'tipo' => 'texto'],
                    ],
                    'filas' => Programa::abiertoSinModulosAbiertos()
                        ->orderBy('nombre')->orderBy('id')
                        ->get(['id', 'codigo', 'nombre'])
                        ->map(fn (Programa $programa) => [$programa->codigo, $programa->nombre, self::SITUACION_VACIO])
                        ->all(),
                    'vacio' => self::SIN_PROGRAMAS_VACIOS,
                ],
            ],
            sinDatos: $sinDatos,
        );
    }
}
