<?php

namespace App\Enums;

enum EstadoPlanificacion: string
{
    case Programado = 'programado';
    case EnCurso = 'en_curso';
    case Finalizado = 'finalizado';
    case Cancelado = 'cancelado';

    /** @return array<int, self> Estados que el CRM puede ver. */
    public static function visiblesParaCrm(): array
    {
        return [self::Programado, self::EnCurso];
    }

    public function label(): string
    {
        return match ($this) {
            self::Programado => 'Programado',
            self::EnCurso => 'En curso',
            self::Finalizado => 'Finalizado',
            self::Cancelado => 'Cancelado',
        };
    }
}
