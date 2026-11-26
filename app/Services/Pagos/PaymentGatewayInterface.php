<?php

namespace App\Services\Pagos;

use App\Models\MetodoPago;
use App\Models\User;

/** Métodos de pago según la pasarela activa. El cobro vive en PagoService. */
interface PaymentGatewayInterface
{
    public function nombre(): string;

    /** @return array{proveedor: string, stripe_payment_method_id: ?string, marca: ?string, exp_mes: ?int, exp_anio: ?int, ultimos_digitos: ?string} */
    public function registrarMetodo(User $usuario, array $datos): array;

    public function eliminarMetodo(MetodoPago $metodo): void;
}
