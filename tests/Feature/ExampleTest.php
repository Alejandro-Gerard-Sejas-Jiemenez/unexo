<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_la_raiz_entra_por_la_spa(): void
    {
        $this->get('/')->assertOk()->assertSee('id="app"', false);
        $this->get('/app')->assertRedirect('/');
        $this->get('/app/catalogo')->assertRedirect('/catalogo');
    }

    public function test_la_spa_responde_en_cualquiera_de_sus_rutas(): void
    {
        foreach (['/', '/usuarios', '/matriculas/cualquier-cosa'] as $ruta) {
            $this->get($ruta)->assertOk()->assertSee('id="app"', false);
        }
    }
}
