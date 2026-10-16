<?php

namespace App\Enums;

enum EstadoComprobante: string
{
    case Pendiente = 'pendiente';
    case Aprobado = 'aprobado';
    case Rechazado = 'rechazado';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'En revisión',
            self::Aprobado => 'Aprobado',
            self::Rechazado => 'Rechazado',
        };
    }
}