<?php

namespace App\Services\Pagos;

use App\Models\Matricula;
use App\Models\MetodoPago;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use UnexpectedValueException;

class StripePaymentGateway implements PaymentGatewayInterface
{
    private const API_BASE = 'https://api.stripe.com/v1';

    public function nombre(): string
    {
        return 'stripe';
    }

    public function asegurarCliente(User $usuario): ?string
    {
        if ($usuario->stripe_customer_id) {
            return $usuario->stripe_customer_id;
        }

        $res = $this->post('/customers', [
            'email' => $usuario->email,
            'name' => $usuario->nombre_completo,
            'metadata[user_id]' => $usuario->id,
        ]);

        $usuario->update(['stripe_customer_id' => $res['id']]);

        return $res['id'];
    }

    public function registrarMetodo(User $usuario, array $datos): array
    {
        if (($datos['tipo'] ?? 'tarjeta') !== 'tarjeta') {
            return [
                'proveedor' => $this->nombre(),
                'stripe_payment_method_id' => null,
                'marca' => null,
                'exp_mes' => null,
                'exp_anio' => null,
                'ultimos_digitos' => null,
            ];
        }

        $customerId = $this->asegurarCliente($usuario);
        $pmId = (string) ($datos['stripe_payment_method_id'] ?? '');

        if ($pmId === '') {
            throw ValidationException::withMessages([
                'stripe_payment_method_id' => 'Se requiere confirmar la tarjeta mediante Stripe antes de guardarla.',
            ]);
        }

        $pm = $this->post("/payment_methods/{$pmId}/attach", ['customer' => $customerId]);

        return [
            'proveedor' => $this->nombre(),
            'stripe_payment_method_id' => $pm['id'] ?? $pmId,
            'marca' => $pm['card']['brand'] ?? null,
            'exp_mes' => isset($pm['card']['exp_month']) ? (int) $pm['card']['exp_month'] : null,
            'exp_anio' => isset($pm['card']['exp_year']) ? (int) $pm['card']['exp_year'] : null,
            'ultimos_digitos' => $pm['card']['last4'] ?? null,
        ];
    }

    public function eliminarMetodo(MetodoPago $metodo): void
    {
        if ($metodo->stripe_payment_method_id) {
            $this->post("/payment_methods/{$metodo->stripe_payment_method_id}/detach", []);
        }
    }

    public function crearIntentoPago(Matricula $matricula, float $monto, ?MetodoPago $metodo, array $metadata = []): array
    {
        $moneda = strtolower((string) config('services.payments.currency', 'bob'));
        $payload = [
            'amount' => (int) round($monto * 100),
            'currency' => $moneda,
            'metadata[matricula_id]' => $matricula->id,
        ];

        if ($matricula->estudiante?->stripe_customer_id) {
            $payload['customer'] = $matricula->estudiante->stripe_customer_id;
        }
        if ($metodo?->stripe_payment_method_id) {
            $payload['payment_method'] = $metodo->stripe_payment_method_id;
            $payload['confirm'] = 'true';
            $payload['automatic_payment_methods[enabled]'] = 'true';
            $payload['automatic_payment_methods[allow_redirects]'] = 'never';
        } else {
            $payload['automatic_payment_methods[enabled]'] = 'true';
        }

        foreach ($metadata as $k => $v) {
            $payload["metadata[{$k}]"] = (string) $v;
        }

        $intent = $this->post('/payment_intents', $payload);
        $estado = match ($intent['status'] ?? 'requires_payment_method') {
            'succeeded' => 'aprobada',
            'processing' => 'procesando',
            'requires_action', 'requires_confirmation', 'requires_payment_method' => 'requiere_accion',
            'canceled' => 'cancelada',
            default => 'pendiente',
        };

        return [
            'proveedor' => $this->nombre(),
            'estado' => $estado,
            'referencia' => 'STR-'.Str::upper(substr((string) ($intent['id'] ?? Str::random(10)), -10)),
            'stripe_payment_intent_id' => $intent['id'] ?? null,
            'stripe_charge_id' => $intent['latest_charge'] ?? null,
            'client_secret' => $intent['client_secret'] ?? null,
            'motivo_rechazo' => $intent['last_payment_error']['message'] ?? null,
        ];
    }

    public function verificarWebhook(string $payload, string $firma): array
    {
        $secret = (string) config('services.stripe.webhook_secret');
        if ($secret === '') {
            throw new RuntimeException('STRIPE_WEBHOOK_SECRET no está configurado.');
        }

        $partes = [];
        foreach (explode(',', $firma) as $segmento) {
            [$clave, $valor] = array_pad(explode('=', trim($segmento), 2), 2, null);
            if ($clave && $valor) {
                $partes[$clave][] = $valor;
            }
        }

        $timestamp = $partes['t'][0] ?? null;
        $firmasV1 = $partes['v1'] ?? [];

        if (! $timestamp || $firmasV1 === []) {
            throw new UnexpectedValueException('Cabecera Stripe-Signature inválida.');
        }

        if (abs(time() - (int) $timestamp) > 300) {
            throw new UnexpectedValueException('Firma de webhook expirada.');
        }

        $esperada = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);
        $valida = false;
        foreach ($firmasV1 as $candidata) {
            if (hash_equals($esperada, $candidata)) {
                $valida = true;
                break;
            }
        }

        if (! $valida) {
            throw new UnexpectedValueException('Firma de webhook no coincide.');
        }

        $evento = json_decode($payload, true);
        if (! is_array($evento) || empty($evento['id']) || empty($evento['type'])) {
            throw new UnexpectedValueException('Evento de Stripe mal formado.');
        }

        return $evento;
    }

    /** @return array<string, mixed> */
    private function post(string $ruta, array $datos): array
    {
        $secret = (string) config('services.stripe.secret');
        if ($secret === '') {
            throw new RuntimeException('STRIPE_SECRET no está configurado.');
        }

        $respuesta = Http::withBasicAuth($secret, '')
            ->asForm()
            ->timeout(15)
            ->post(self::API_BASE.$ruta, $datos);

        if ($respuesta->failed()) {
            $mensaje = $respuesta->json('error.message');
            throw ValidationException::withMessages([
                'pago' => $mensaje
                    ? "Stripe rechazó la operación: {$mensaje}"
                    : 'No pudimos completar la comunicación con Stripe. Inténtalo de nuevo.',
            ]);
        }

        return $respuesta->json();
    }
}
