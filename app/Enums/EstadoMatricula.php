<?php

namespace App\Enums;

enum EstadoMatricula: string
{
    case Pendiente = 'pendiente';
    case Activo = 'activo';
    case Retirado = 'retirado';

    /** @return array<int, self> Estados cuyas matrículas cuentan como inscritos de un grupo. */
    public static function queCuentanComoInscritos(): array
    {
        return [self::Pendiente, self::Activo];
    }

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente de pago',
            self::Activo => 'Activo (pagado)',
            self::Retirado => 'Retirado',
        };
    }
}
