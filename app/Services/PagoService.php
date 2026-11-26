<?php

namespace App\Services;

use App\Enums\EstadoMatricula;
use App\Models\Matricula;
use App\Models\MetodoPago;
use App\Models\Transaccion;
use App\Models\User;
use App\Services\Pagos\PaymentGatewayInterface;
use App\Services\Pagos\StripeCheckout;
use App\Services\Pagos\StripeException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PagoService
{
    private const SIN_CONEXION = 'No pudimos conectar con la pasarela de pago. Inténtalo de nuevo.';

    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
        private readonly StripeCheckout $stripe,
    ) {}

    public function registrarMetodo(User $usuario, array $datos): MetodoPago
    {
        $datosGateway = $this->gateway->registrarMetodo($usuario, $datos);

        return DB::transaction(function () use ($usuario, $datos, $datosGateway) {
            $predeterminado = (bool) ($datos['predeterminado'] ?? false)
                || ! $usuario->metodosPago()->exists();

            if ($predeterminado) {
                $usuario->metodosPago()->update(['predeterminado' => false]);
            }

            return $usuario->metodosPago()->create([
                'tipo' => $datos['tipo'],
                'titular' => $datos['titular'],
                'ultimos_digitos' => $datosGateway['ultimos_digitos'],
                'vencimiento' => $datos['vencimiento'] ?? null,
                'etiqueta' => $datos['etiqueta'] ?? null,
                'predeterminado' => $predeterminado,
                'proveedor' => $datosGateway['proveedor'],
                'stripe_payment_method_id' => $datosGateway['stripe_payment_method_id'],
                'marca' => $datosGateway['marca'],
                'exp_mes' => $datosGateway['exp_mes'],
                'exp_anio' => $datosGateway['exp_anio'],
            ]);
        });
    }

    public function eliminarMetodo(MetodoPago $metodo): void
    {
        $this->gateway->eliminarMetodo($metodo);

        DB::transaction(function () use ($metodo) {
            $usuario = $metodo->usuario;
            $eraPredeterminado = $metodo->predeterminado;
            $metodo->delete();

            if ($eraPredeterminado && $usuario) {
                $usuario->metodosPago()->latest()->limit(1)->update(['predeterminado' => true]);
            }
        });
    }

    /**
     * Paga la matrícula completa. El importe lo fija el sistema.
     *
     * @return array{transaccion: Transaccion, url: ?string}
     */
    public function pagarMatricula(Matricula $matricula, ?MetodoPago $metodo, User $autor): array
    {
        $cargo = $this->conMatriculaBloqueada($matricula, function (Matricula $actual) use ($metodo, $autor) {
            $this->validarMatriculaCobrable($actual);
            $cargos = $actual->transacciones()->get();

            if ($cargos->contains(fn (Transaccion $c) => $c->esCuota())) {
                $this->fallar('matricula', 'Esta matrícula tiene un plan de cuotas: paga cada cuota.');
            }

            if ($existente = $cargos->first()) {
                return $existente;
            }

            $importe = $actual->importe() ?? 0.0;
            if ($importe <= 0) {
                $this->fallar('monto', 'Esta matrícula todavía no tiene un importe por pagar. Consulta con administración.');
            }

            if (! $this->enModoStripe()) {
                $this->exigirMetodo($actual, $metodo);
            }

            return $this->nuevoCargo($actual, $importe, $metodo, $autor);
        });

        return $this->pagar($cargo, $metodo, $autor);
    }

    /**
     * Paga un cargo ya registrado (pago completo o cuota).
     *
     * @return array{transaccion: Transaccion, url: ?string}
     */
    public function pagar(Transaccion $cargo, ?MetodoPago $metodo, User $autor): array
    {
        $this->validarMatriculaCobrable($cargo->matricula);

        if ($cargo->estaPagado()) {
            $this->fallar('transaccion', 'Este pago ya fue realizado.');
        }

        if ($this->enModoStripe()) {
            return $this->pagarEnStripe($cargo);
        }

        if ($cargo->usoStripe()) {
            $this->fallar('transaccion', 'Este pago se inició en Stripe y solo puede continuarse con Stripe activado.');
        }

        $this->exigirMetodo($cargo->matricula, $metodo);

        $pagado = $this->conCargoBloqueado($cargo, function (Transaccion $actual) use ($metodo) {
            if ($actual->estaPagado() || $actual->usoStripe()) {
                $this->fallar('transaccion', 'Este pago ya fue realizado o está en curso.');
            }

            $actual->update([
                'metodo_pago_id' => $metodo->id,
                'proveedor' => 'simulado',
                'estado' => 'aprobada',
                'fecha_proceso' => now(),
                'motivo_rechazo' => null,
            ]);
            $this->activarSiEstaPagada($actual->matricula);

            return $actual;
        });

        return ['transaccion' => $pagado, 'url' => null];
    }

    /**
     * Cargo único que registra el administrador. En modo simulado se paga al instante
     * si eligió un método del estudiante; si no, queda por pagar.
     */
    public function registrarCargo(Matricula $matricula, ?MetodoPago $metodo, float $monto, User $autor): Transaccion
    {
        $this->validarMetodoDelEstudiante($matricula, $metodo);

        $cargo = $this->conMatriculaBloqueada($matricula, function (Matricula $actual) use ($metodo, $monto, $autor) {
            $this->validarSinCargos($actual);

            return $this->nuevoCargo($actual, $monto, $metodo, $autor);
        });

        if ($this->enModoStripe() || ! $metodo) {
            return $cargo;
        }

        return $this->pagar($cargo, $metodo, $autor)['transaccion'];
    }

    /** @return array<int, Transaccion> */
    public function generarPlanDePagos(
        Matricula $matricula,
        ?MetodoPago $metodo,
        float $total,
        int $cuotas,
        User $autor,
    ): array {
        $this->validarMetodoDelEstudiante($matricula, $metodo);

        return $this->conMatriculaBloqueada($matricula, function (Matricula $actual) use ($metodo, $total, $cuotas, $autor) {
            $this->validarSinCargos($actual);

            $enCentavos = (int) round($total * 100);
            $base = intdiv($enCentavos, $cuotas);
            $hoy = now();

            return array_map(fn (int $numero) => $this->nuevoCargo(
                $actual,
                ($numero === $cuotas ? $enCentavos - $base * ($cuotas - 1) : $base) / 100,
                $metodo,
                $autor,
                [
                    'numero_cuota' => $numero,
                    'total_cuotas' => $cuotas,
                    'fecha_vencimiento' => $hoy->copy()->addMonths($numero - 1)->toDateString(),
                ],
            ), range(1, $cuotas));
        });
    }

    public function aprobar(Transaccion $transaccion): Transaccion
    {
        return $this->conCargoBloqueado($transaccion, function (Transaccion $actual) {
            $this->validarResolucionManual($actual);
            $actual->update(['estado' => 'aprobada', 'fecha_proceso' => now(), 'motivo_rechazo' => null]);
            $this->activarSiEstaPagada($actual->matricula);

            return $actual->fresh('metodoPago');
        });
    }

    /** El cargo rechazado sigue por pagar. */
    public function rechazar(Transaccion $transaccion, ?string $motivo = null): Transaccion
    {
        return $this->conCargoBloqueado($transaccion, function (Transaccion $actual) use ($motivo) {
            $this->validarResolucionManual($actual);
            $actual->update(['estado' => 'rechazada', 'fecha_proceso' => now(), 'motivo_rechazo' => $motivo]);

            return $actual->fresh('metodoPago');
        });
    }

    /**
     * Pago al que pertenece un aviso auténtico, aunque no llegue a cambiarlo.
     *
     * @param  array<string, mixed>  $objeto
     */
    public function pagoDelAviso(array $objeto): ?string
    {
        if ($cargo = $this->cargoDeSesion($objeto)) {
            return $cargo->id;
        }

        // Un aviso atrasado de un intento anterior sigue perteneciendo a su pago.
        $id = $objeto['metadata']['transaccion_id'] ?? null;

        return $id && Str::isUuid($id) ? Transaccion::whereKey($id)->value('id') : null;
    }

    /**
     * Aplica una confirmación auténtica de Stripe. Si no corresponde, no cambia nada.
     *
     * @param  array<string, mixed>  $sesion
     */
    public function confirmarSesion(array $sesion): void
    {
        $cargo = $this->cargoDeSesion($sesion);

        if (! $cargo || ($sesion['payment_status'] ?? null) !== 'paid') {
            return;
        }

        $this->conCargoBloqueado($cargo, function (Transaccion $actual) use ($sesion) {
            if (! $this->sesionVigente($actual, $sesion) || $actual->estaPagado()) {
                return;
            }

            $coincide = (int) ($sesion['amount_total'] ?? 0) === $this->enCentavos($actual)
                && strtolower((string) ($sesion['currency'] ?? '')) === $actual->moneda;

            if (! $coincide || $actual->matricula->estado === EstadoMatricula::Retirado) {
                // En esta fase se revisa en el panel de Stripe.
                Log::warning('Cobro de Stripe sin aplicar', ['transaccion' => $actual->id, 'sesion' => $sesion['id']]);

                return;
            }

            $intento = $sesion['payment_intent'] ?? null;
            $actual->update([
                'estado' => 'aprobada',
                'fecha_proceso' => now(),
                'stripe_checkout_session_id' => $sesion['id'],
                'stripe_payment_intent_id' => is_array($intento) ? ($intento['id'] ?? null) : $intento,
                'motivo_rechazo' => null,
            ]);
            $this->activarSiEstaPagada($actual->matricula);
        });
    }

    /**
     * Devuelve el cargo a «por pagar». Solo cuando consta que ese intento ya no puede cobrar.
     *
     * @param  array<string, mixed>  $sesion
     */
    public function liberarSesion(array $sesion, string $estado, ?string $motivo = null): void
    {
        $cargo = $this->cargoDeSesion($sesion);

        if (! $cargo) {
            return;
        }

        $this->conCargoBloqueado($cargo, function (Transaccion $actual) use ($sesion, $estado, $motivo) {
            if ($actual->estado !== 'procesando' || ! $this->sesionVigente($actual, $sesion)) {
                return;
            }

            $actual->update([
                'estado' => $estado,
                'stripe_checkout_session_id' => null,
                'motivo_rechazo' => $motivo,
            ]);
        });
    }

    /** @return array{transaccion: Transaccion, url: ?string} */
    private function pagarEnStripe(Transaccion $cargo): array
    {
        if ($cargo->stripe_checkout_session_id) {
            try {
                $sesion = $this->stripe->consultarSesion($cargo->stripe_checkout_session_id);
            } catch (StripeException) {
                $this->fallar('pago', self::SIN_CONEXION);
            }

            $estado = $sesion['status'] ?? null;

            if ($estado === 'open') {
                return ['transaccion' => $cargo, 'url' => $sesion['url']];
            }

            if ($estado === 'complete' && ($sesion['payment_status'] ?? null) === 'paid') {
                $this->confirmarSesion($sesion);
                $actual = $cargo->fresh();

                if (! $actual->estaPagado()) {
                    $this->fallar('pago', 'No pudimos aplicar este pago. Consulta con administración.');
                }

                return ['transaccion' => $actual, 'url' => null];
            }

            if ($estado === 'complete') {
                // Pago diferido: sigue en curso salvo que conste que falló.
                if (! in_array($sesion['payment_intent']['status'] ?? null, ['requires_payment_method', 'canceled'], true)) {
                    return ['transaccion' => $cargo, 'url' => null];
                }
                $this->liberarSesion($sesion, 'rechazada', 'El pago no se completó.');
            } elseif ($estado === 'expired') {
                $this->liberarSesion($sesion, 'pendiente');
            } else {
                $this->fallar('pago', self::SIN_CONEXION);
            }

            $cargo = $cargo->fresh();
        }

        // El intento se reserva antes de llamar a Stripe: sin respuesta, queda «en curso».
        [$cargo, $esNuevo] = $this->conCargoBloqueado($cargo, function (Transaccion $actual) {
            if ($actual->estaPagado()) {
                $this->fallar('transaccion', 'Este pago ya fue realizado.');
            }

            if ($actual->estado === 'procesando') {
                return [$actual, false];
            }

            $actual->update([
                'estado' => 'procesando',
                'proveedor' => 'stripe',
                'stripe_checkout_session_id' => null,
                'stripe_intento' => $actual->stripe_intento + 1,
                'metadata' => ['intento_iniciado' => now()->toIso8601String()],
                'motivo_rechazo' => null,
            ]);

            return [$actual, true];
        });

        if ($cargo->stripe_checkout_session_id) {
            return $this->pagarEnStripe($cargo);
        }

        // Pasadas 24 h Stripe olvida la clave de idempotencia y repetir podría crear un segundo cobro.
        $iniciado = $cargo->metadata['intento_iniciado'] ?? null;
        if (! $esNuevo && (! $iniciado || Carbon::parse($iniciado)->lt(now()->subHours(23)))) {
            $this->fallar('pago', 'No pudimos comprobar el pago anterior. Consulta con administración.');
        }

        try {
            $sesion = $this->stripe->crearSesion(
                $this->datosDeSesion($cargo),
                "cargo-{$cargo->id}-{$cargo->stripe_intento}",
            );
        } catch (StripeException $e) {
            Log::warning('No se pudo iniciar el pago en Stripe', ['transaccion' => $cargo->id, 'detalle' => $e->getMessage()]);

            // En un reintento, un error no demuestra que la primera petición no creara nada.
            if ($esNuevo && $e->definitiva) {
                $cargo->update(['estado' => 'pendiente']);
                $this->fallar('pago', 'No pudimos iniciar el pago. Consulta con administración.');
            }

            $this->fallar('pago', self::SIN_CONEXION);
        }

        $cargo->update(['stripe_checkout_session_id' => $sesion['id']]);

        return ['transaccion' => $cargo, 'url' => $sesion['url'] ?? null];
    }

    /** @return array<string, mixed> */
    private function datosDeSesion(Transaccion $cargo): array
    {
        $matricula = $cargo->matricula;
        $concepto = $cargo->esCuota() ? "Cuota {$cargo->numero_cuota} de {$cargo->total_cuotas}" : 'Matrícula';
        $programa = $matricula->cohorte?->programa?->nombre;
        $identificacion = ['transaccion_id' => $cargo->id, 'intento' => (string) $cargo->stripe_intento];

        return [
            'mode' => 'payment',
            'client_reference_id' => $cargo->id,
            'customer_email' => $matricula->estudiante?->email,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => $cargo->moneda,
                    'unit_amount' => $this->enCentavos($cargo),
                    'product_data' => ['name' => $programa ? "{$concepto} · {$programa}" : $concepto],
                ],
            ]],
            'metadata' => $identificacion,
            'payment_intent_data' => ['metadata' => $identificacion],
            'success_url' => url('/pagos').'?pago=retorno&cargo='.$cargo->id,
            'cancel_url' => url('/pagos').'?pago=cancelado&cargo='.$cargo->id,
        ];
    }

    /** @param array<string, mixed> $sesion */
    private function cargoDeSesion(array $sesion): ?Transaccion
    {
        if (empty($sesion['id'])) {
            return null;
        }

        if ($cargo = Transaccion::where('stripe_checkout_session_id', $sesion['id'])->first()) {
            return $cargo;
        }

        // El aviso puede llegar aunque la respuesta al crear la sesión se haya perdido.
        $id = $sesion['metadata']['transaccion_id'] ?? null;
        $cargo = $id && Str::isUuid($id) ? Transaccion::find($id) : null;

        return $cargo && $this->sesionVigente($cargo, $sesion) ? $cargo : null;
    }

    /** @param array<string, mixed> $sesion */
    private function sesionVigente(Transaccion $cargo, array $sesion): bool
    {
        if ($cargo->stripe_checkout_session_id) {
            return $cargo->stripe_checkout_session_id === ($sesion['id'] ?? null);
        }

        return $cargo->estado === 'procesando'
            && (string) $cargo->stripe_intento === (string) ($sesion['metadata']['intento'] ?? '');
    }

    public function saldoPendiente(Matricula $matricula, ?Collection $transacciones = null): float
    {
        if ($transacciones !== null) {
            return (float) $transacciones->whereIn('estado', ['pendiente', 'requiere_accion', 'procesando'])->sum('monto');
        }

        return (float) $matricula->transacciones()
            ->whereIn('estado', ['pendiente', 'requiere_accion', 'procesando'])
            ->sum('monto');
    }

    public function totalPagado(Matricula $matricula, ?Collection $transacciones = null): float
    {
        if ($transacciones !== null) {
            return (float) $transacciones->where('estado', 'aprobada')->sum('monto');
        }

        return (float) $matricula->transacciones()->where('estado', 'aprobada')->sum('monto');
    }

    private function validarMatriculaCobrable(Matricula $matricula): void
    {
        if ($matricula->estado === EstadoMatricula::Retirado) {
            $this->fallar('matricula', 'No se pueden registrar pagos sobre una matrícula retirada.');
        }
    }

    private function validarSinCargos(Matricula $matricula): void
    {
        $this->validarMatriculaCobrable($matricula);

        if ($matricula->transacciones()->exists()) {
            $this->fallar('monto', 'Esta matrícula ya tiene un pago o un plan registrado.');
        }
    }

    private function validarResolucionManual(Transaccion $cargo): void
    {
        $this->validarMatriculaCobrable($cargo->matricula);

        if (! $cargo->admiteResolucionManual()) {
            $this->fallar('transaccion', 'Solo se puede resolver a mano un cargo por pagar que no haya pasado por Stripe.');
        }
    }

    private function validarMetodoDelEstudiante(Matricula $matricula, ?MetodoPago $metodo): void
    {
        if ($metodo && $metodo->user_id !== $matricula->estudiante_id) {
            $this->fallar('metodo_pago_id', 'El método de pago elegido no pertenece al estudiante de esta matrícula.');
        }
    }

    private function exigirMetodo(Matricula $matricula, ?MetodoPago $metodo): void
    {
        if (! $metodo) {
            $this->fallar('metodo_pago_id', 'Registra un método de pago antes de pagar.');
        }

        $this->validarMetodoDelEstudiante($matricula, $metodo);
    }

    /** Se activa solo si ningún cargo de la matrícula queda sin pagar. */
    private function activarSiEstaPagada(Matricula $matricula): void
    {
        if (
            $matricula->estado === EstadoMatricula::Pendiente
            && $matricula->transacciones()->exists()
            && ! $matricula->transacciones()->where('estado', '!=', 'aprobada')->exists()
        ) {
            $matricula->update(['estado' => EstadoMatricula::Activo]);
        }
    }

    private function nuevoCargo(Matricula $matricula, float $monto, ?MetodoPago $metodo, User $autor, array $extra = []): Transaccion
    {
        return Transaccion::create($extra + [
            'matricula_id' => $matricula->id,
            'metodo_pago_id' => $metodo?->id,
            'monto' => $monto,
            'estado' => 'pendiente',
            'referencia' => $this->referencia(),
            'registrada_por' => $autor->id,
            'proveedor' => $this->enModoStripe() ? 'stripe' : 'simulado',
            'moneda' => strtolower((string) config('services.payments.currency', 'bob')),
        ]);
    }

    /** El bloqueo de la matrícula serializa las solicitudes simultáneas. */
    private function conMatriculaBloqueada(Matricula $matricula, callable $accion): mixed
    {
        return DB::transaction(fn () => $accion(Matricula::lockForUpdate()->findOrFail($matricula->id)));
    }

    private function conCargoBloqueado(Transaccion $cargo, callable $accion): mixed
    {
        return $this->conMatriculaBloqueada($cargo->matricula, function (Matricula $matricula) use ($cargo, $accion) {
            $actual = Transaccion::lockForUpdate()->findOrFail($cargo->id);
            $actual->setRelation('matricula', $matricula);

            return $accion($actual);
        });
    }

    private function enModoStripe(): bool
    {
        return config('services.payments.driver') === 'stripe';
    }

    private function enCentavos(Transaccion $cargo): int
    {
        return (int) round((float) $cargo->monto * 100);
    }

    private function fallar(string $campo, string $mensaje): never
    {
        throw ValidationException::withMessages([$campo => $mensaje]);
    }

    private function referencia(): string
    {
        return 'TRX-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
    }
}
