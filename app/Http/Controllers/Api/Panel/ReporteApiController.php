<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\FiltrosReporteMatriculasRequest;
use App\Http\Requests\FiltrosReporteOcupacionRequest;
use App\Http\Requests\ReporteCatalogoRequest;
use App\Services\ReporteCatalogoService;
use App\Services\ReporteMatriculasService;
use App\Services\ReporteOcupacionService;
use App\Support\Reporte;
use Illuminate\Http\JsonResponse;

/**
 * CU8: los tres reportes, servidos en JSON a la SPA.
 *
 * Usa los mismos Services y Form Requests que la pantalla Blade y las
 * descargas, así que los datos y el orden de las filas coinciden en los cuatro
 * destinos por construcción.
 */
class ReporteApiController extends Controller
{
    public function matriculas(
        FiltrosReporteMatriculasRequest $request,
        ReporteMatriculasService $servicio,
    ): JsonResponse {
        return $this->responder(
            $servicio->generar($request->filtros()),
            $request->datosFormulario(),
        );
    }

    public function ocupacion(
        FiltrosReporteOcupacionRequest $request,
        ReporteOcupacionService $servicio,
    ): JsonResponse {
        return $this->responder(
            $servicio->generar($request->filtros()),
            $request->datosFormulario(),
        );
    }

    public function catalogo(
        ReporteCatalogoRequest $request,
        ReporteCatalogoService $servicio,
    ): JsonResponse {
        return $this->responder($servicio->generar(), $request->datosFormulario());
    }

    /**
     * Serializa el reporte. Las celdas van ya formateadas con las mismas reglas
     * que usan la pantalla y el PDF, para no reimplementarlas en JavaScript.
     */
    private function responder(Reporte $reporte, array $extra): JsonResponse
    {
        return response()->json([
            'titulo' => $reporte->titulo,
            'generado_en' => $reporte->fechaGeneracion(),
            'sin_datos' => $reporte->sinDatos,
            'aviso_sin_datos' => Reporte::AVISO_SIN_DATOS,
            'filtros_aplicados' => $reporte->filtros,
            'totales' => $reporte->totales,
            'secciones' => array_map(fn (array $seccion) => [
                'titulo' => $seccion['titulo'],
                'columnas' => $seccion['columnas'],
                'vacio' => $seccion['vacio'] ?? null,
                'filas' => array_map(
                    fn (array $fila) => array_map(
                        fn (mixed $celda, int $i) => Reporte::formatear(
                            $celda,
                            $seccion['columnas'][$i]['tipo'] ?? 'texto',
                        ),
                        $fila,
                        array_keys($fila),
                    ),
                    $seccion['filas'],
                ),
            ], $reporte->secciones),
            'opciones' => $extra,
        ]);
    }
}
