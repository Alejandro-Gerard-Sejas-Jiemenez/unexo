<?php

namespace App\Http\Controllers\Api\Panel;

use App\Enums\EstadoComprobante;
use App\Enums\EstadoMatricula;
use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Requests\ComprobanteStoreRequest;
use App\Models\Comprobante;
use App\Models\Matricula;
use App\Services\MatriculaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComprobanteApiController extends Controller
{
    public function store(ComprobanteStoreRequest $request, Matricula $matricula): JsonResponse
    {
        abort_unless($matricula->estudiante_id === $request->user()->id, 403, 'No tienes permiso para subir comprobantes a esta matrícula.');

        if ($matricula->estado !== EstadoMatricula::Pendiente) {
            throw ValidationException::withMessages([
                'archivo' => 'Solo se puede subir un comprobante a una matrícula pendiente de pago.',
            ]);
        }

        $comprobante = $matricula->comprobantes()->create([
            'archivo_ruta' => $request->file('archivo')->store('comprobantes'),
            'estado' => EstadoComprobante::Pendiente,
            'subido_por' => $request->user()->id,
        ]);

        return response()->json([
            'comprobante' => [
                'id' => $comprobante->id,
                'estado' => $comprobante->estado->value,
                'estado_label' => $comprobante->estado->label(),
                'url_archivo' => url('/api/panel/comprobantes/'.$comprobante->id),
                'creado' => $comprobante->created_at?->format('d/m/Y H:i'),
            ],
            'mensaje' => 'Comprobante enviado. La administración lo revisará.',
        ], 201);
    }

    public function ver(Request $request, Comprobante $comprobante): StreamedResponse
    {
        $usuario = $request->user();
        abort_unless(
            $usuario->rol === Rol::Administrador || $comprobante->matricula->estudiante_id === $usuario->id,
            403,
            'No tienes permiso para ver este comprobante.',
        );

        return Storage::response($comprobante->archivo_ruta);
    }

    public function aprobar(Request $request, Comprobante $comprobante, MatriculaService $servicio): JsonResponse
    {
        $this->resolver($request, $comprobante, EstadoComprobante::Aprobado);

        if ($comprobante->matricula->estado === EstadoMatricula::Pendiente) {
            $servicio->cambiarEstado($comprobante->matricula, EstadoMatricula::Activo);
        }

        return response()->json([
            'mensaje' => 'Pago aprobado: la matrícula quedó activa.',
        ]);
    }

    public function rechazar(Request $request, Comprobante $comprobante): JsonResponse
    {
        $this->resolver($request, $comprobante, EstadoComprobante::Rechazado);

        return response()->json([
            'mensaje' => 'Comprobante rechazado.',
        ]);
    }

    private function resolver(Request $request, Comprobante $comprobante, EstadoComprobante $estado): void
    {
        if ($comprobante->estado !== EstadoComprobante::Pendiente) {
            throw ValidationException::withMessages([
                'comprobante' => 'Este comprobante ya fue revisado.',
            ]);
        }

        $comprobante->update([
            'estado' => $estado,
            'verificado_por' => $request->user()->id,
            'fecha_verificacion' => now(),
        ]);
    }
}
