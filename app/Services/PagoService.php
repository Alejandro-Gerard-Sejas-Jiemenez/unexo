<?php

namespace App\Services;

use App\Enums\EstadoMatricula;
use App\Models\Matricula;
use App\Models\MetodoPago;
use App\Models\Transaccion;
use App\Models\User;
use App\Services\Pagos\PaymentGatewayInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PagoService
{
    public function __construct(private readonly PaymentGatewayInterface $gateway) {}

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

    /** @return array{transaccion: Transaccion, client_secret: ?string} */
    public function procesarPagoUnico(
        Matricula $matricula,
        ?MetodoPago $metodo,
        float $monto,
        User $autor,
    ): array {
        $this->validarMatriculaCobrable($matricula);
        $this->validarMetodoDelEstudiante($matricula, $metodo);

        $intento = $this->gateway->crearIntentoPago($matricula, $monto, $metodo, [
            'registrada_por' => $autor->id,
        ]);

        $transaccion = DB::transaction(function () use ($matricula, $metodo, $monto, $autor, $intento) {
            $transaccion = Transaccion::create([
                'matricula_id' => $matricula->id,
                'metodo_pago_id' => $metodo?->id,
                'monto' => $monto,
                'estado' => $intento['estado'],
                'referencia' => $intento['referencia'],
                'fecha_proceso' => $intento['estado'] === 'aprobada' ? now() : null,
                'registrada_por' => $autor->id,
                'proveedor' => $intento['proveedor'],
                'moneda' => strtolower((string) config('services.payments.currency', 'bob')),
                'stripe_payment_intent_id' => $intento['stripe_payment_intent_id'],
                'stripe_charge_id' => $intento['stripe_charge_id'],
                'motivo_rechazo' => $intento['motivo_rechazo'],
            ]);

            if ($transaccion->estado === 'aprobada') {
                $this->activarSiEstaPagada($matricula);
            }

            return $transaccion;
        });

        return [
            'transaccion' => $transaccion,
            'client_secret' => $intento['client_secret'],
        ];
    }

    /** @return array{transaccion: Transaccion, client_secret: ?string} */
    public function pagarCuota(
        Transaccion $transaccion,
        ?MetodoPago $metodo,
        User $autor,
    ): array {
        if (! in_array($transaccion->estado, ['pendiente', 'requiere_accion', 'rechazada'], true)) {
            throw ValidationException::withMessages([
                'transaccion' => 'Esta cuota ya fue pagada o procesada.',
            ]);
        }

        $matricula = $transaccion->matricula;
        $this->validarMatriculaCobrable($matricula);
        $this->validarMetodoDelEstudiante($matricula, $metodo);

        $intento = $this->gateway->crearIntentoPago($matricula, (float) $transaccion->monto, $metodo, [
            'transaccion_id' => $transaccion->id,
            'registrada_por' => $autor->id,
        ]);

        $actualizada = DB::transaction(function () use ($transaccion, $metodo, $matricula, $intento) {
            $transaccion->update([
                'metodo_pago_id' => $metodo?->id ?? $transaccion->metodo_pago_id,
                'estado' => $intento['estado'],
                'fecha_proceso' => $intento['estado'] === 'aprobada' ? now() : null,
                'proveedor' => $intento['proveedor'],
                'stripe_payment_intent_id' => $intento['stripe_payment_intent_id'],
                'stripe_charge_id' => $intento['stripe_charge_id'],
                'motivo_rechazo' => $intento['motivo_rechazo'],
            ]);

            if ($intento['estado'] === 'aprobada') {
                $this->activarSiEstaPagada($matricula);
            }

            return $transaccion->fresh('metodoPago');
        });

        return [
            'transaccion' => $actualizada,
            'client_secret' => $intento['client_secret'],
        ];
    }

    /** @return array<int, Transaccion> */
    public function generarPlanDePagos(
        Matricula $matricula,
        ?MetodoPago $metodo,
        float $total,
        int $cuotas,
        User $autor,
    ): array {
        $this->validarMatriculaCobrable($matricula);
        $this->validarMetodoDelEstudiante($matricula, $metodo);

        return DB::transaction(function () use ($matricula, $metodo, $total, $cuotas, $autor) {
            $enCentavos = (int) round($total * 100);
            $base = intdiv($enCentavos, $cuotas);
            $ahora = now();
            $moneda = strtolower((string) config('services.payments.currency', 'bob'));
            $filas = [];

            for ($numero = 1; $numero <= $cuotas; $numero++) {
                $centavos = $numero === $cuotas
                    ? $enCentavos - $base * ($cuotas - 1)
                    : $base;

                $filas[] = [
                    'id' => (string) Str::uuid(),
                    'matricula_id' => $matricula->id,
                    'metodo_pago_id' => $metodo?->id,
                    'monto' => $centavos / 100,
                    'numero_cuota' => $numero,
                    'total_cuotas' => $cuotas,
                    'estado' => 'pendiente',
                    'referencia' => $this->referencia(),
                    'registrada_por' => $autor->id,
                    'proveedor' => $this->gateway->nombre(),
                    'moneda' => $moneda,
                    'fecha_vencimiento' => $ahora->copy()->addMonths($numero - 1)->toDateString(),
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ];
            }

            Transaccion::insert($filas);

            return $matricula->transacciones()
                ->whereIn('referencia', array_column($filas, 'referencia'))
                ->orderBy('numero_cuota')
                ->get()
                ->all();
        });
    }

    public function aprobar(Transaccion $transaccion): Transaccion
    {
        if ($transaccion->estado === 'aprobada') {
            throw ValidationException::withMessages([
                'transaccion' => 'Esta transacción ya fue aprobada.',
            ]);
        }

        if (in_array($transaccion->estado, ['reembolsada', 'cancelada'], true)) {
            throw ValidationException::withMessages([
                'transaccion' => 'No se puede aprobar una transacción anulada.',
            ]);
        }

        return DB::transaction(function () use ($transaccion) {
            $transaccion->update([
                'estado' => 'aprobada',
                'fecha_proceso' => now(),
                'motivo_rechazo' => null,
            ]);
            $this->activarSiEstaPagada($transaccion->matricula);

            return $transaccion->fresh('metodoPago');
        });
    }

    public function rechazar(Transaccion $transaccion, ?string $motivo = null): Transaccion
    {
        if (in_array($transaccion->estado, ['aprobada', 'reembolsada', 'cancelada'], true)) {
            throw ValidationException::withMessages([
                'transaccion' => 'No se puede rechazar una transacción ya aprobada o cerrada.',
            ]);
        }

        $transaccion->update([
            'estado' => 'rechazada',
            'fecha_proceso' => now(),
            'motivo_rechazo' => $motivo,
        ]);

        return $transaccion->fresh('metodoPago');
    }

    public function confirmarPorIntent(string $intentId, ?string $chargeId = null): ?Transaccion
    {
        $transaccion = Transaccion::where('stripe_payment_intent_id', $intentId)->first();
        if (! $transaccion) {
            return null;
        }

        return DB::transaction(function () use ($transaccion, $chargeId) {
            $transaccion->update([
                'estado' => 'aprobada',
                'fecha_proceso' => now(),
                'stripe_charge_id' => $chargeId ?? $transaccion->stripe_charge_id,
                'motivo_rechazo' => null,
            ]);
            $this->activarSiEstaPagada($transaccion->matricula);

            return $transaccion->fresh();
        });
    }

    public function rechazarPorIntent(string $intentId, ?string $motivo = null): ?Transaccion
    {
        $transaccion = Transaccion::where('stripe_payment_intent_id', $intentId)->first();
        if (! $transaccion) {
            return null;
        }

        $transaccion->update([
            'estado' => 'rechazada',
            'fecha_proceso' => now(),
            'motivo_rechazo' => $motivo,
        ]);

        return $transaccion->fresh();
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
            throw ValidationException::withMessages([
                'matricula' => 'No se pueden registrar pagos sobre una matrícula retirada.',
            ]);
        }
    }

    private function validarMetodoDelEstudiante(Matricula $matricula, ?MetodoPago $metodo): void
    {
        if ($metodo && $metodo->user_id !== $matricula->estudiante_id) {
            throw ValidationException::withMessages([
                'metodo_pago_id' => 'El método de pago elegido no pertenece al estudiante de esta matrícula.',
            ]);
        }
    }

    private function activarSiEstaPagada(Matricula $matricula): void
    {
        $conteos = $matricula->transacciones()
            ->selectRaw("SUM(CASE WHEN estado IN ('pendiente','requiere_accion','procesando') THEN 1 ELSE 0 END) as pendientes")
            ->selectRaw("SUM(CASE WHEN estado = 'aprobada' THEN 1 ELSE 0 END) as aprobadas")
            ->first();

        if (
            (int) ($conteos->pendientes ?? 0) === 0
            && (int) ($conteos->aprobadas ?? 0) > 0
            && $matricula->estado !== EstadoMatricula::Retirado
        ) {
            $matricula->update(['estado' => EstadoMatricula::Activo]);
        }
    }

    private function referencia(): string
    {
        return 'TRX-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
    }
}
