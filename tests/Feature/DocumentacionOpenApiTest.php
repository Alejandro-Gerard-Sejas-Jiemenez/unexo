<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/** Spec 013: documentación navegable de la API. */
class DocumentacionOpenApiTest extends TestCase
{
    use RefreshDatabase;

    private function permitir(): void
    {
        // Las pruebas no corren en «local»: se autoriza para poder leer la documentación.
        Gate::define('viewApiDocs', fn ($usuario = null) => true);
    }

    public function test_ca01_la_pagina_y_el_esquema_describen_las_entradas(): void
    {
        $this->permitir();

        $this->get('/docs/api')->assertOk()->assertSee('elements-api', false)->assertDontSee('name="base-path"', false);

        $rutas = $this->getJson('/docs/api.json')->assertOk()->json('paths');
        $alta = $rutas['/v1/estudiantes-convertidos']['post']['requestBody']['content']['application/json']['schema'];

        $esquema = isset($alta['$ref'])
            ? $this->getJson('/docs/api.json')->json('components.schemas.'.basename($alta['$ref']))
            : $alta;

        $this->assertArrayHasKey('id_externo_crm', $esquema['properties']);
        $this->assertArrayHasKey('cohorte_id', $esquema['properties']);
        foreach (['id_externo_crm', 'nombres', 'apellidos', 'email'] as $campo) {
            $this->assertContains($campo, $esquema['required']);
        }
        $this->assertArrayHasKey('requestBody', $rutas['/panel/usuarios']['post']);
    }

    public function test_ca02_fuera_de_local_se_niega_el_acceso(): void
    {
        $this->get('/docs/api')->assertForbidden();
        $this->get('/docs/api.json')->assertForbidden();
    }

    public function test_ca03_cada_operacion_declara_su_autenticacion(): void
    {
        $this->permitir();
        $documento = $this->getJson('/docs/api.json')->json();
        $esquemas = $documento['components']['securitySchemes'];

        $this->assertSame(['http', 'bearer'], [$esquemas['tokenCrm']['type'], $esquemas['tokenCrm']['scheme']]);
        $this->assertSame(['apiKey', 'cookie'], [$esquemas['sesionPanel']['type'], $esquemas['sesionPanel']['in']]);

        $de = fn (string $ruta, string $metodo) => $documento['paths'][$ruta][$metodo]['security'] ?? null;

        $this->assertSame([['tokenCrm' => []]], $de('/v1/estudiantes-convertidos', 'post'));
        $this->assertSame([['tokenCrm' => []]], $de('/v1/crm/catalogo', 'get'));
        $this->assertSame([['sesionPanel' => []]], $de('/panel/usuarios', 'post'));
        $this->assertSame([], $de('/panel/login', 'post'));
        $this->assertSame([], $de('/panel/visitas', 'get'));
        $this->assertSame([], $de('/webhooks/stripe', 'post'));
    }

    public function test_ca04_la_aplicacion_sigue_respondiendo_en_sus_direcciones(): void
    {
        $this->get('/catalogo')->assertOk()->assertSee('name="base-path"', false);
    }
}
