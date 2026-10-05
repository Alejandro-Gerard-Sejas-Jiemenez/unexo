<?php

namespace App\Enums;

enum EstadoComercial: string
{
    case Borrador = 'borrador';
    case Abierto = 'abierto';
    case Cerrado = 'cerrado';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Abierto => 'Abierto',
            self::Cerrado => 'Cerrado',
        };
    }
}
