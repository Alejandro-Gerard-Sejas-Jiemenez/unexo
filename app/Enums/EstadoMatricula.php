<?php

namespace App\Enums;

enum EstadoMatricula: string
{
    case Pendiente = 'pendiente';
    case Activo = 'activo';
    case Retirado = 'retirado';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente de pago',
            self::Activo => 'Activo (pagado)',
            self::Retirado => 'Retirado',
        };
    }
}
