<?php

namespace App\Enums;

enum Rol: string
{
    case Administrador = 'administrador';
    case Docente = 'docente';
    case Estudiante = 'estudiante';

    public function label(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador',
            self::Docente => 'Docente',
            self::Estudiante => 'Estudiante',
        };
    }
}
