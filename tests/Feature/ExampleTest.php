<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_la_raiz_entra_por_la_spa(): void
    {
        $this->get('/')->assertRedirect('/app');
    }

    public function test_la_spa_responde_en_cualquiera_de_sus_rutas(): void
    {
        // El enrutado interno lo resuelve React Router: el servidor devuelve
        // siempre el mismo shell, sin importar la subruta.
        foreach (['/app', '/app/usuarios', '/app/matriculas/cualquier-cosa'] as $ruta) {
            $this->get($ruta)->assertOk()->assertSee('id="app"', false);
        }
    }
}
