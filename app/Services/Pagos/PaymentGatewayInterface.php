<?php

namespace App\Services\Pagos;

use App\Models\Matricula;
use App\Models\MetodoPago;
use App\Models\User;

interface PaymentGatewayInterface
{
    public function nombre(): string;

    public function asegurarCliente(User $usuario): ?string;

    /** @return array{proveedor: string, stripe_payment_method_id: ?string, marca: ?string, exp_mes: ?int, exp_anio: ?int, ultimos_digitos: ?string} */
    public function registrarMetodo(User $usuario, array $datos): array;

    public function eliminarMetodo(MetodoPago $metodo): void;

    /** @return array{proveedor: string, estado: string, referencia: string, stripe_payment_intent_id: ?string, stripe_charge_id: ?string, client_secret: ?string, motivo_rechazo: ?string} */
    public function crearIntentoPago(Matricula $matricula, float $monto, ?MetodoPago $metodo, array $metadata = []): array;

    /** @return array{id: string, type: string, data: array<string, mixed>} */
    public function verificarWebhook(string $payload, string $firma): array;
}
