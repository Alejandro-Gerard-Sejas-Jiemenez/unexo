<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Programa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginacionYLimitesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['rol' => Rol::Administrador]);
    }

    public function test_el_listado_se_pagina_segun_la_configuracion(): void
    {
        foreach (range(1, 12) as $n) {
            Programa::create(['codigo' => sprintf('P-%02d', $n), 'nombre' => sprintf('Programa %02d', $n), 'version' => '1.0']);
        }
        $admin = $this->admin();

        $this->actingAs($admin)->getJson('/api/panel/catalogo/programas')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', 12)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2);

        $this->actingAs($admin)->getJson('/api/panel/catalogo/programas?page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    public function test_el_cambio_de_pagina_conserva_la_busqueda(): void
    {
        foreach (range(1, 12) as $n) {
            Programa::create(['codigo' => sprintf('DA-%02d', $n), 'nombre' => sprintf('Datos %02d', $n), 'version' => '1.0']);
        }
        Programa::create(['codigo' => 'X-1', 'nombre' => 'Otro programa', 'version' => '1.0']);

        $res = $this->actingAs($this->admin())
            ->getJson('/api/panel/catalogo/programas?q=Datos')
            ->assertOk();

        $nombres = collect($res->json('data'))->pluck('nombre')->all();
        $this->assertNotContains('Otro programa', $nombres);
        $this->assertSame(12, $res->json('meta.total'));
    }

    public function test_el_limite_por_pagina_se_puede_cambiar_en_un_solo_lugar(): void
    {
        config(['limites.por_pagina' => 3]);
        foreach (range(1, 5) as $n) {
            Programa::create(['codigo' => "P-$n", 'nombre' => "Programa $n", 'version' => '1.0']);
        }

        $this->actingAs($this->admin())->getJson('/api/panel/catalogo/programas')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_el_backend_rechaza_textos_mas_largos_que_el_limite(): void
    {
        $datos = [
            'rol' => Rol::Estudiante->value,
            'nombres' => str_repeat('a', config('limites.usuarios.nombres') + 1),
            'apellidos' => 'Perez',
            'email' => 'nuevo@nexo.test',
            'password' => 'Demo12345',
        ];

        $this->actingAs($this->admin())->postJson('/api/panel/usuarios', $datos)
            ->assertStatus(422)
            ->assertJsonValidationErrors('nombres');

        $datos['nombres'] = str_repeat('a', config('limites.usuarios.nombres'));
        $this->actingAs($this->admin())->postJson('/api/panel/usuarios', $datos)
            ->assertCreated();
    }

    public function test_la_api_expone_los_mismos_limites_del_backend(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->getJson('/api/panel/usuarios/opciones')
            ->assertOk()
            ->assertJsonPath('limites.nombres', config('limites.usuarios.nombres'))
            ->assertJsonPath('limites.email', config('limites.usuarios.email'));

        $this->actingAs($admin)->getJson('/api/panel/catalogo/opciones')
            ->assertOk()
            ->assertJsonPath('limites.programas.descripcion', config('limites.programas.descripcion'))
            ->assertJsonPath('limites.modulos.duracion_horas', config('limites.modulos.duracion_horas'));
    }

    public function test_los_selects_se_recortan_segun_el_limite(): void
    {
        config(['limites.opciones_select' => 2]);
        User::factory()->count(3)->create(['rol' => Rol::Estudiante]);

        $this->actingAs($this->admin())->getJson('/api/panel/matriculas/opciones')
            ->assertOk()
            ->assertJsonCount(2, 'estudiantes');
    }
}
