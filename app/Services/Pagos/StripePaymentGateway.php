<?php

namespace App\Services\Pagos;

use App\Models\MetodoPago;
use App\Models\User;

/** En modo Stripe el sistema no maneja tarjetas: solo registra QR y transferencia. */
class StripePaymentGateway implements PaymentGatewayInterface
{
    public function nombre(): string
    {
        return 'stripe';
    }

    public function registrarMetodo(User $usuario, array $datos): array
    {
        return [
            'proveedor' => $this->nombre(),
            'stripe_payment_method_id' => null,
            'marca' => null,
            'exp_mes' => null,
            'exp_anio' => null,
            'ultimos_digitos' => null,
        ];
    }

    public function eliminarMetodo(MetodoPago $metodo): void
    {
        // Sin recurso externo.
    }
}
