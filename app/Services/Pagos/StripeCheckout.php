<?php

namespace App\Services\Pagos;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

/** Comunicación con Stripe Checkout (página de pago alojada por Stripe). */
class StripeCheckout
{
    private const API_BASE = 'https://api.stripe.com/v1';

    /** @return array<string, mixed> */
    public function crearSesion(array $datos, string $claveIdempotencia): array
    {
        return $this->enviar(fn (PendingRequest $http) => $http
            ->withHeaders(['Idempotency-Key' => $claveIdempotencia])
            ->asForm()
            ->post(self::API_BASE.'/checkout/sessions', $datos));
    }

    /** @return array<string, mixed> */
    public function consultarSesion(string $id): array
    {
        return $this->enviar(fn (PendingRequest $http) => $http
            ->get(self::API_BASE."/checkout/sessions/{$id}", ['expand' => ['payment_intent']]));
    }

    /**
     * La firma se exige siempre, sea cual sea la pasarela activa.
     *
     * @return array{id: string, type: string, data: array<string, mixed>}
     */
    public function verificarWebhook(string $payload, string $firma): array
    {
        $secreto = (string) config('services.stripe.webhook_secret');

        $partes = [];
        foreach (explode(',', $firma) as $segmento) {
            [$clave, $valor] = array_pad(explode('=', trim($segmento), 2), 2, null);
            if ($clave && $valor) {
                $partes[$clave][] = $valor;
            }
        }

        $momento = $partes['t'][0] ?? null;

        if ($secreto === '' || ! $momento || abs(time() - (int) $momento) > 300) {
            throw new UnexpectedValueException('Aviso no auténtico.');
        }

        $esperada = hash_hmac('sha256', "{$momento}.{$payload}", $secreto);

        if (! collect($partes['v1'] ?? [])->contains(fn ($v) => hash_equals($esperada, $v))) {
            throw new UnexpectedValueException('Aviso no auténtico.');
        }

        $evento = json_decode($payload, true);
        if (! is_array($evento) || empty($evento['id']) || empty($evento['type'])) {
            throw new UnexpectedValueException('Aviso mal formado.');
        }

        return $evento;
    }

    /** @return array<string, mixed> */
    private function enviar(callable $peticion): array
    {
        $secreto = (string) config('services.stripe.secret');
        if ($secreto === '') {
            throw new StripeException('Falta la clave secreta de Stripe.', definitiva: true);
        }

        try {
            $respuesta = $peticion(Http::withBasicAuth($secreto, '')
                ->withHeaders(['Stripe-Version' => (string) config('services.stripe.api_version')])
                ->timeout(15));
        } catch (ConnectionException) {
            throw new StripeException('Stripe no respondió.');
        }

        if ($respuesta->failed()) {
            // 409, 429, 5xx y los choques de idempotencia no aclaran si se creó algo.
            $definitiva = $respuesta->clientError()
                && ! in_array($respuesta->status(), [409, 429], true)
                && $respuesta->json('error.type') !== 'idempotency_error';

            throw new StripeException('Stripe devolvió el estado '.$respuesta->status().'.', $definitiva);
        }

        return (array) $respuesta->json();
    }
}
