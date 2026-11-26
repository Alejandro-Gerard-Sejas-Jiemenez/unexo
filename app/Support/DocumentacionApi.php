<?php

namespace App\Support;

use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use Dedoc\Scramble\Support\Generator\SecurityScheme;

/** Completa el esquema que genera Scramble con la autenticación de cada parte de la API. */
class DocumentacionApi
{
    /** Rutas que se llaman sin token ni sesión; el webhook se protege con la firma de Stripe. */
    private const SIN_AUTENTICACION = ['panel/login', 'panel/visitas', 'webhooks/stripe'];

    public static function completar(OpenApi $documento): void
    {
        $documento->info->description = <<<'TEXTO'
            **API del CRM** (`/v1`): token Bearer que se genera en «Acceso API».

            **API del panel** (`/panel`): usa la sesión iniciada en la aplicación y, al enviar datos, la
            protección CSRF. Probarla desde esta página puede fallar.

            **Webhook** (`/webhooks/stripe`): lo llama Stripe con la cabecera firmada `Stripe-Signature`.
            TEXTO;

        $documento->components->addSecurityScheme('tokenCrm', SecurityScheme::http('bearer'));
        $documento->components->addSecurityScheme(
            'sesionPanel',
            SecurityScheme::apiKey('cookie', (string) config('session.cookie')),
        );

        $documento->security = [];

        foreach ($documento->paths as $path) {
            $ruta = trim($path->path, '/');

            $requisito = match (true) {
                in_array($ruta, self::SIN_AUTENTICACION, true) => null,
                str_starts_with($ruta, 'v1/') => 'tokenCrm',
                default => 'sesionPanel',
            };

            foreach ($path->operations as $operacion) {
                $operacion->security = $requisito ? [new SecurityRequirement([$requisito => []])] : [];
            }
        }
    }
}
