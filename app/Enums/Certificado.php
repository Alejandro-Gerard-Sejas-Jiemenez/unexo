<?php

namespace App\Enums;

enum Certificado: string
{
    case Certificado = 'certificado';
    case SinCertificado = 'sin_certificado';

    public function label(): string
    {
        return match ($this) {
            self::Certificado => 'Certificado',
            self::SinCertificado => 'Sin certificado',
        };
    }
}
