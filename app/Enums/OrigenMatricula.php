<?php

namespace App\Enums;

enum OrigenMatricula: string
{
    case Crm = 'crm';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Crm => 'Desde el CRM',
            self::Manual => 'Carga manual',
        };
    }
}
