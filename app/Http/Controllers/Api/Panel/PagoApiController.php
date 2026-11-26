<?php

namespace App\Http\Controllers\Api\Panel;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Requests\EstudiantePagoRequest;
use App\Http\Requests\MetodoPagoRequest;
use App\Http\Requests\PagoRequest;
use App\Http\Resources\MatriculaPanelResource;
use App\Http\Resources\MetodoPagoResource;
use App\Http\Resources\TransaccionResource;
use App\Models\Matricula;
use App\Models\MetodoPago;
use App\Models\Transaccion;
use App\Models\User;
use App\Services\PagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;

class PagoApiController extends Controller
{
    public function __construct(private readonly PagoService $pagos) {}

    public function metodos(Request $request): AnonymousResourceCollection
    {
        return MetodoPagoResource::collection($this->metodosDisponibles($request->user()))
            ->additional(['pasarela' => ['driver' => config('services.payments.driver', 'simulado')]]);
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
        abort_unless($metodo->user_id === $request->user()->id, 403, 'Ese método de pago no es tuyo.');

        $this->pagos->eliminarMetodo($metodo);

        return response()->json(['mensaje' => 'Método de pago eliminado.']);
    }

    public function misMatriculas(Request $request): JsonResponse
    {
        $matriculas = Matricula::where('estudiante_id', $request->user()->id)
            ->with(['cohorte.programa', 'inscripciones.grupo.modulo', 'transacciones.metodoPago'])
            ->orderByDesc('fecha_matricula')
            ->get();

        return response()->json([
            'data' => $matriculas->map(fn (Matricula $m) => [
                'matricula' => new MatriculaPanelResource($m),
                'transacciones' => TransaccionResource::collection($m->transacciones),
                'resumen' => [
                    'pagado' => $this->pagos->totalPagado($m, $m->transacciones),
                    'pendiente' => $this->pagos->saldoPendiente($m, $m->transacciones),
                    'estado_matricula' => $m->estado->value,
                ],
            ])->values(),
            'pasarela' => [
                'driver' => config('services.payments.driver', 'simulado'),
                'moneda' => strtoupper((string) config('services.payments.currency', 'bob')),
                'stripe_public_key' => config('services.stripe.key'),
            ],
        ]);
    }

    public function transacciones(Request $request, Matricula $matricula): JsonResponse
    {
        $this->verificarAcceso($request, $matricula);
        $transacciones = $matricula->transacciones()->with('metodoPago')->orderBy('numero_cuota')->orderBy('created_at')->get();

        return response()->json([
            'data' => TransaccionResource::collection($transacciones),
            'metodos_estudiante' => $request->user()->rol === Rol::Administrador
                ? MetodoPagoResource::collection($matricula->estudiante ? $this->metodosDisponibles($matricula->estudiante) : collect())
                : [],
            'resumen' => [
                'pagado' => $this->pagos->totalPagado($matricula, $transacciones),
                'pendiente' => $this->pagos->saldoPendiente($matricula, $transacciones),
                'estado_matricula' => $matricula->estado->value,
            ],
            'pasarela' => [
                'driver' => config('services.payments.driver', 'simulado'),
                'moneda' => strtoupper((string) config('services.payments.currency', 'bob')),
                'stripe_public_key' => config('services.stripe.key'),
            ],
        ]);
    }

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
            $cargo = $this->pagos->registrarCargo($matricula, $metodo, (float) $datos['monto'], $request->user());

            $mensaje = $cargo->estado === 'aprobada'
                ? 'Pago registrado y aprobado.'
                : 'Cargo registrado: queda por pagar.';
        }

        $transacciones = $matricula->transacciones()->with('metodoPago')->orderBy('numero_cuota')->orderBy('created_at')->get();

        return response()->json([
            'mensaje' => $mensaje,
            'data' => TransaccionResource::collection($transacciones),
            'resumen' => [
                'pagado' => $this->pagos->totalPagado($matricula, $transacciones),
                'pendiente' => $this->pagos->saldoPendiente($matricula, $transacciones),
                'estado_matricula' => $matricula->fresh()->estado->value,
            ],
        ], 201);
    }

    public function pagarMatricula(EstudiantePagoRequest $request, Matricula $matricula): JsonResponse
    {
        $this->verificarPuedePagar($request, $matricula);

        $resultado = $this->pagos->pagarMatricula($matricula, $this->metodoElegido($request, $matricula), $request->user());

        return $this->respuestaDePago($resultado, 201);
    }

    public function pagarTransaccion(EstudiantePagoRequest $request, Transaccion $transaccion): JsonResponse
    {
        $matricula = $transaccion->matricula;
        $this->verificarPuedePagar($request, $matricula);

        $resultado = $this->pagos->pagar($transaccion, $this->metodoElegido($request, $matricula), $request->user());

        return $this->respuestaDePago($resultado, 200);
    }

    /** @param array{transaccion: Transaccion, url: ?string} $resultado */
    private function respuestaDePago(array $resultado, int $codigo): JsonResponse
    {
        $cargo = $resultado['transaccion']->load('metodoPago');

        return response()->json([
            'transaccion' => new TransaccionResource($cargo),
            'url' => $resultado['url'],
            'mensaje' => match (true) {
                $cargo->estado === 'aprobada' => 'Pago procesado correctamente.',
                $resultado['url'] !== null => 'Te llevamos a la página de pago.',
                default => 'Estamos confirmando tu pago.',
            },
        ], $codigo);
    }

    private function metodoElegido(Request $request, Matricula $matricula): ?MetodoPago
    {
        $id = $request->input('metodo_pago_id');

        return $id
            ? MetodoPago::find($id)
            : $matricula->estudiante?->metodosPago()->orderByDesc('predeterminado')->first();
    }

    /** En modo Stripe no se ofrecen tarjetas: se ingresan solo en la página de Stripe. */
    private function metodosDisponibles(User $usuario): Collection
    {
        return $usuario->metodosPago()
            ->when(config('services.payments.driver') === 'stripe', fn ($q) => $q->where('tipo', '!=', 'tarjeta'))
            ->get();
    }

    /** En modo Stripe solo paga el dueño de la matrícula. */
    private function verificarPuedePagar(Request $request, Matricula $matricula): void
    {
        $this->verificarAcceso($request, $matricula);

        abort_if(
            config('services.payments.driver') === 'stripe' && $matricula->estudiante_id !== $request->user()->id,
            403,
            'Solo el estudiante puede pagar su matrícula en Stripe.',
        );
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

    private function verificarAcceso(Request $request, Matricula $matricula): void
    {
        $usuario = $request->user();

        abort_unless(
            $usuario->rol === Rol::Administrador || $matricula->estudiante_id === $usuario->id,
            403,
            'No tienes permiso para acceder a los pagos de esta matrícula.',
        );
    }
}
