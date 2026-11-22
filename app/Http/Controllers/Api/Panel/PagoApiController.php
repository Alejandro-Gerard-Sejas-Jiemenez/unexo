<?php

namespace App\Http\Controllers\Api\Panel;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Requests\MetodoPagoRequest;
use App\Http\Requests\PagoRequest;
use App\Http\Resources\MetodoPagoResource;
use App\Http\Resources\TransaccionResource;
use App\Models\Matricula;
use App\Models\MetodoPago;
use App\Models\Transaccion;
use App\Services\PagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PagoApiController extends Controller
{
    public function __construct(private readonly PagoService $pagos) {}

    // ---------------------------------------------------------------- métodos

    /** Cada usuario administra sus propios métodos de pago. */
    public function metodos(Request $request): AnonymousResourceCollection
    {
        return MetodoPagoResource::collection($request->user()->metodosPago()->get());
    }

    public function guardarMetodo(MetodoPagoRequest $request): JsonResponse
    {
        $metodo = $this->pagos->registrarMetodo($request->user(), $request->validated());

        return response()->json([
            'metodo' => new MetodoPagoResource($metodo),
            'mensaje' => 'Método de pago registrado.',
        ], 201);
    }

    public function eliminarMetodo(Request $request, MetodoPago $metodo): JsonResponse
    {
        // Un usuario no puede borrar el método de otro, ni siquiera el administrador.
        abort_unless($metodo->user_id === $request->user()->id, 403, 'Ese método de pago no es tuyo.');

        $this->pagos->eliminarMetodo($metodo);

        return response()->json(['mensaje' => 'Método de pago eliminado.']);
    }

    // ------------------------------------------------------------ operaciones

    /** Transacciones de una matrícula, con su saldo. */
    public function transacciones(Request $request, Matricula $matricula): JsonResponse
    {
        $this->verificarAcceso($request, $matricula);

        return response()->json([
            'data' => TransaccionResource::collection(
                $matricula->transacciones()->with('metodoPago')->get(),
            ),
            'resumen' => [
                'pagado' => $this->pagos->totalPagado($matricula),
                'pendiente' => $this->pagos->saldoPendiente($matricula),
                'estado_matricula' => $matricula->estado->value,
            ],
        ]);
    }

    /** Pago único o plan de cuotas, según venga o no el campo 'cuotas'. */
    public function cobrar(PagoRequest $request, Matricula $matricula): JsonResponse
    {
        $datos = $request->validated();
        $metodo = isset($datos['metodo_pago_id'])
            ? MetodoPago::find($datos['metodo_pago_id'])
            : null;

        if (! empty($datos['cuotas'])) {
            $this->pagos->generarPlanDePagos(
                $matricula,
                $metodo,
                (float) $datos['monto'],
                (int) $datos['cuotas'],
                $request->user(),
            );

            $mensaje = "Plan de {$datos['cuotas']} cuotas generado.";
        } else {
            $this->pagos->procesarPagoUnico(
                $matricula,
                $metodo,
                (float) $datos['monto'],
                $request->user(),
            );

            $mensaje = 'Pago registrado y aprobado.';
        }

        return response()->json([
            'mensaje' => $mensaje,
            'data' => TransaccionResource::collection(
                $matricula->transacciones()->with('metodoPago')->get(),
            ),
            'resumen' => [
                'pagado' => $this->pagos->totalPagado($matricula),
                'pendiente' => $this->pagos->saldoPendiente($matricula),
                'estado_matricula' => $matricula->fresh()->estado->value,
            ],
        ], 201);
    }

    public function aprobar(Transaccion $transaccion): JsonResponse
    {
        return response()->json([
            'transaccion' => new TransaccionResource($this->pagos->aprobar($transaccion)),
            'mensaje' => 'Cuota aprobada.',
        ]);
    }

    public function rechazar(Transaccion $transaccion): JsonResponse
    {
        return response()->json([
            'transaccion' => new TransaccionResource($this->pagos->rechazar($transaccion)),
            'mensaje' => 'Cuota rechazada.',
        ]);
    }

    /** El estudiante solo ve sus propias matrículas; el administrador, todas. */
    private function verificarAcceso(Request $request, Matricula $matricula): void
    {
        $usuario = $request->user();

        abort_unless(
            $usuario->rol === Rol::Administrador || $matricula->estudiante_id === $usuario->id,
            403,
            'No tienes permiso para ver los pagos de esta matrícula.',
        );
    }
}
