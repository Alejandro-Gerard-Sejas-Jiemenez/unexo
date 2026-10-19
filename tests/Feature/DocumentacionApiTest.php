<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentacionApiTest extends TestCase
{
    use RefreshDatabase;

    // CA-34/RF-27: la documentación no muestra campos retirados y sí los vigentes.
    public function test_la_documentacion_de_la_api_refleja_lo_que_se_publica(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Administrador]);

        $respuesta = $this->actingAs($admin)->get(route('clientes-api.index'));

        $respuesta->assertOk()
            ->assertDontSee('creditos', false)
            ->assertDontSee('tipo_evaluacion', false)
            ->assertDontSee('nivel_academico', false)
            ->assertDontSee('perfil_egreso', false)
            ->assertSee('crm/ediciones', false)
            ->assertSee('prerrequisito', false)
            ->assertSee('horarios', false);
    }
}
