<?php

namespace App\Enums;

enum Modalidad: string
{
    case Virtual = 'virtual';
    case Presencial = 'presencial';
    case Mixta = 'mixta';

    public function label(): string
    {
        return match ($this) {
            self::Virtual => 'Virtual',
            self::Presencial => 'Presencial',
            self::Mixta => 'Mixta (Virtual y Presencial)',
        };
    }
}
