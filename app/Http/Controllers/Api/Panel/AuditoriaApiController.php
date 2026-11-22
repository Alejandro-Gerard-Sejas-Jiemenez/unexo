<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Http\Resources\BitacoraResource;
use App\Services\BitacoraService;
use App\Services\EstadisticaService;
use App\Services\VisitaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuditoriaApiController extends Controller
{
    public function __construct(
        private readonly BitacoraService $bitacora,
        private readonly EstadisticaService $estadisticas,
        private readonly VisitaService $visitas,
    ) {}

    /** Bitácora de auditoría: solo el administrador. */
    public function bitacora(Request $request): AnonymousResourceCollection
    {
        return BitacoraResource::collection(
            $this->bitacora->listar($request->only(['evento', 'q', 'desde', 'hasta'])),
        );
    }

    /** Panel analítico: métricas del negocio y del flujo de accesos. */
    public function estadisticas(): JsonResponse
    {
        return response()->json($this->estadisticas->resumen());
    }

    /**
     * Contador de visitas para el pie de página. Lo puede ver cualquier usuario
     * autenticado, porque se muestra en todas las vistas del sitio.
     */
    public function visitas(Request $request): JsonResponse
    {
        return response()->json([
            'total' => $this->visitas->totalDelSitio(),
            'pagina' => $this->visitas->deLaRuta((string) $request->query('ruta', '/')),
        ]);
    }
}
