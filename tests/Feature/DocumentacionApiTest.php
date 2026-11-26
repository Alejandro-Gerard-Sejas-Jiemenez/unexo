<?php

namespace Tests\Feature;

use Tests\TestCase;

class DocumentacionApiTest extends TestCase
{
    // CA-34/RF-27: la documentación en ClientesApi refleja lo que se publica.
    public function test_la_documentacion_de_la_api_refleja_lo_que_se_publica(): void
    {
        $contenido = file_get_contents(resource_path('js/spa/paginas/sistema/ClientesApi.jsx'));

        $this->assertIsString($contenido);
        $this->assertStringNotContainsString('creditos', $contenido);
        $this->assertStringNotContainsString('tipo_evaluacion', $contenido);
        $this->assertStringNotContainsString('nivel_academico', $contenido);
        $this->assertStringNotContainsString('perfil_egreso', $contenido);
        $this->assertStringContainsString('crm/ediciones', $contenido);
        $this->assertStringContainsString('prerrequisito', $contenido);
        $this->assertStringContainsString('horarios', $contenido);
    }
}
