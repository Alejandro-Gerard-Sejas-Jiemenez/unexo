<?php

namespace App\Services\Pagos;

use RuntimeException;

class StripeException extends RuntimeException
{
    /** @param bool $definitiva true si consta que Stripe no creó nada con esa petición */
    public function __construct(string $mensaje, public readonly bool $definitiva = false)
    {
        parent::__construct($mensaje);
    }
}
