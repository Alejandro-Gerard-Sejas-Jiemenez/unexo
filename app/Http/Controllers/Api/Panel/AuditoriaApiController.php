<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\BitacoraFiltrosRequest;
use App\Http\Requests\VisitaRequest;
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

    public function bitacora(BitacoraFiltrosRequest $request): AnonymousResourceCollection
    {
        return BitacoraResource::collection(
            $this->bitacora->listar($request->validated()),
        );
    }

    public function estadisticas(): JsonResponse
    {
        return response()->json($this->estadisticas->resumen());
    }

    public function visitas(Request $request): JsonResponse
    {
        $ruta = $this->normalizarRuta((string) $request->query('ruta', '/'));

        return response()->json([
            'ruta' => $ruta,
            'total' => $this->visitas->totalDelSitio(),
            'pagina' => $this->visitas->deLaRuta($ruta),
        ]);
    }

    public function registrarVisita(VisitaRequest $request): JsonResponse
    {
        $ruta = $this->normalizarRuta((string) $request->validated('ruta'));
        $this->visitas->registrar($ruta);

        return response()->json([
            'ruta' => $ruta,
            'total' => $this->visitas->totalDelSitio(),
            'pagina' => $this->visitas->deLaRuta($ruta),
        ]);
    }

    private function normalizarRuta(string $ruta): string
    {
        $limpia = '/'.trim(explode('?', $ruta, 2)[0], '/');
        $limpia = preg_replace('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i', ':id', $limpia) ?? $limpia;

        return $limpia === '' ? '/' : $limpia;
    }
}
