<?php

namespace App\Enums;

enum Modalidad: string
{
    case Virtual = 'virtual';
    case Presencial = 'presencial';

    public function label(): string
    {
        return match ($this) {
            self::Virtual => 'Virtual',
            self::Presencial => 'Presencial',
        };
    }
}
