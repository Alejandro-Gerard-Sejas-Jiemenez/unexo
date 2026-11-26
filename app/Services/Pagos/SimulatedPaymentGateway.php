<?php

namespace App\Services\Pagos;

use App\Models\MetodoPago;
use App\Models\User;

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
}
