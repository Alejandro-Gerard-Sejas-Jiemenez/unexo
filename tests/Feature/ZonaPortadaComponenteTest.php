<?php

namespace Tests\Feature;

use Tests\TestCase;

class ZonaPortadaComponenteTest extends TestCase
{
    public function test_renderiza_etiqueta_boton_accept_y_atributos_de_datos(): void
    {
        $html = (string) $this->withViewErrors([])->blade('<x-zona-portada />');

        $this->assertStringContainsString('data-portada', $html);
        $this->assertStringContainsString('role="group"', $html);
        $this->assertStringContainsString('aria-labelledby="imagen_portada_url-etiqueta"', $html);
        $this->assertStringContainsString('Imagen de portada', $html);
        $this->assertStringContainsString('data-portada-max-kb="'.config('limites.programas.portada_kb').'"', $html);
        $this->assertStringContainsString('accept="image/jpeg,image/png,image/webp"', $html);
        $this->assertStringContainsString('for="imagen_portada_url"', $html);
        $this->assertStringContainsString('name="imagen_portada_url"', $html);
        $this->assertStringContainsString('data-portada-elegir', $html);
        $this->assertStringContainsString('Elegir imagen</button>', $html);
        $this->assertStringContainsString('aria-live="polite"', $html);
        $this->assertStringNotContainsString('Imagen actual', $html);
        $this->assertStringNotContainsString('style=', $html);
    }

    public function test_muestra_la_portada_actual(): void
    {
        $html = (string) $this->withViewErrors([])->blade('<x-zona-portada portada="/storage/programas/demo.jpg" />');

        $this->assertStringContainsString('Imagen actual', $html);
        $this->assertStringContainsString('data-portada-actual="'.asset('/storage/programas/demo.jpg').'"', $html);
        $this->assertStringContainsString('src="'.asset('/storage/programas/demo.jpg').'"', $html);
    }
}
