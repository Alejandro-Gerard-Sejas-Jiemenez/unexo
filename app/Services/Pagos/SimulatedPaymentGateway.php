<?php

namespace App\Services\Pagos;

use App\Models\Matricula;
use App\Models\MetodoPago;
use App\Models\User;
use Illuminate\Support\Str;
use UnexpectedValueException;

class SimulatedPaymentGateway implements PaymentGatewayInterface
{
    public function nombre(): string
    {
        return 'simulado';
    }

    public function asegurarCliente(User $usuario): ?string
    {
        return $usuario->stripe_customer_id;
    }

    public function registrarMetodo(User $usuario, array $datos): array
    {
        $digitos = isset($datos['numero'])
            ? substr(preg_replace('/\D/', '', (string) $datos['numero']), -4)
            : null;

        $expMes = null;
        $expAnio = null;
        if (! empty($datos['vencimiento']) && preg_match('/^(\d{2})\/(\d{2})$/', (string) $datos['vencimiento'], $m)) {
            $expMes = (int) $m[1];
            $expAnio = 2000 + (int) $m[2];
        }

        return [
            'proveedor' => $this->nombre(),
            'stripe_payment_method_id' => null,
            'marca' => ($datos['tipo'] ?? '') === 'tarjeta' ? 'simulada' : null,
            'exp_mes' => $expMes,
            'exp_anio' => $expAnio,
            'ultimos_digitos' => $digitos,
        ];
    }

    public function eliminarMetodo(MetodoPago $metodo): void
    {
        // Sin recurso externo en modo simulado.
    }

    public function crearIntentoPago(Matricula $matricula, float $monto, ?MetodoPago $metodo, array $metadata = []): array
    {
        return [
            'proveedor' => $this->nombre(),
            'estado' => 'aprobada',
            'referencia' => 'TRX-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
            'stripe_payment_intent_id' => null,
            'stripe_charge_id' => null,
            'client_secret' => null,
            'motivo_rechazo' => null,
        ];
    }

    public function verificarWebhook(string $payload, string $firma): array
    {
        $evento = json_decode($payload, true);
        if (! is_array($evento) || empty($evento['id']) || empty($evento['type'])) {
            throw new UnexpectedValueException('Payload de webhook inválido.');
        }

        return $evento;
    }
}
