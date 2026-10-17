<?php

namespace App\Services;

use App\Support\Reporte;
use DateTimeInterface;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Throwable;

/** Convierte cualquier Reporte en un .xlsx; no conoce los reportes concretos. */
class ReporteExcelService
{
    /** @return string ruta de un archivo temporal .xlsx (quien lo entrega debe borrarlo) */
    public function generar(Reporte $reporte): string
    {
        $ruta = tempnam(sys_get_temp_dir(), 'reporte');
        $negrita = (new Style)->setFontBold();
        $entero = new Style;
        $porcentaje = (new Style)->setFormat('0.0');
        $fecha = (new Style)->setFormat('dd/mm/yyyy');

        $escritor = new Writer;

        try {
            $escritor->openToFile($ruta);
            $escritor->getCurrentSheet()->setName('Reporte');

            $escritor->addRow(Row::fromValues([$reporte->titulo], $negrita));
            $escritor->addRow(new Row([
                Cell::fromValue('Generado el'),
                Cell::fromValue($reporte->generadoEn->toDateTimeImmutable(), (new Style)->setFormat('dd/mm/yyyy hh:mm')),
            ]));

            $escritor->addRow(Row::fromValues([]));
            $escritor->addRow(Row::fromValues(['Filtros aplicados'], $negrita));
            foreach ($reporte->filtros as $filtro) {
                $escritor->addRow(Row::fromValues([$filtro['etiqueta'], $filtro['valor']]));
            }

            $escritor->addRow(Row::fromValues([]));
            $escritor->addRow(Row::fromValues(['Totales'], $negrita));
            foreach ($reporte->totales as $total) {
                $escritor->addRow(Row::fromValues([$total['etiqueta'], $total['valor']]));
            }

            if ($reporte->sinDatos) {
                $escritor->addRow(Row::fromValues([]));
                $escritor->addRow(Row::fromValues([Reporte::AVISO_SIN_DATOS]));
            }

            foreach ($reporte->secciones as $seccion) {
                $escritor->addRow(Row::fromValues([]));
                $escritor->addRow(Row::fromValues([$seccion['titulo']], $negrita));
                $escritor->addRow(Row::fromValues(array_map($this->encabezado(...), $seccion['columnas']), $negrita));

                foreach ($seccion['filas'] as $fila) {
                    $escritor->addRow(new Row(array_map(
                        fn ($valor, $columna) => $this->celda($valor, $columna['tipo'], $entero, $porcentaje, $fecha),
                        $fila,
                        $seccion['columnas'],
                    )));
                }

                if ($seccion['filas'] === [] && isset($seccion['vacio'])) {
                    $escritor->addRow(Row::fromValues([$seccion['vacio']]));
                }
            }

            $escritor->close();
        } catch (Throwable $e) {
            @unlink($ruta);
            throw $e;
        }

        return $ruta;
    }

    private function encabezado(array $columna): string
    {
        return $columna['tipo'] === 'porcentaje' ? "{$columna['titulo']} (%)" : $columna['titulo'];
    }

    /** Cantidades y porcentajes quedan como números y las fechas como fechas, no como texto. */
    private function celda(mixed $valor, string $tipo, Style $entero, Style $porcentaje, Style $fecha): Cell
    {
        if ($valor === null) {
            return Cell::fromValue(null);
        }

        return match ($tipo) {
            'entero' => Cell::fromValue((int) $valor, $entero),
            'porcentaje' => Cell::fromValue((float) $valor, $porcentaje),
            'fecha' => Cell::fromValue($valor instanceof DateTimeInterface ? $valor : new \DateTimeImmutable((string) $valor), $fecha),
            default => Cell::fromValue((string) $valor),
        };
    }
}
