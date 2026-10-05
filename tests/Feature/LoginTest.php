<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $estado = 'activo'): User
    {
        return User::factory()->create([
            'rol' => Rol::Estudiante,
            'email' => 'ana@nexo.test',
            'correo_institucional' => 'ana@nexo.edu.test',
            'password' => 'Demo12345',
            'estado' => $estado,
        ]);
    }

    public function test_la_pantalla_de_acceso_se_muestra_sin_menu(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Te damos la bienvenida')
            ->assertSee('type="email"', false)
            ->assertDontSee('class="superior"', false);
    }

    public function test_se_puede_entrar_con_el_correo_personal_o_el_institucional(): void
    {
        $this->usuario();

        $this->post(route('login'), ['usuario' => 'ana@nexo.test', 'password' => 'Demo12345'])->assertRedirect(route('panel'));
        $this->post(route('logout'));
        $this->post(route('login'), ['usuario' => 'ana@nexo.edu.test', 'password' => 'Demo12345'])->assertRedirect(route('panel'));
    }

    public function test_un_dato_incorrecto_muestra_un_solo_mensaje_claro(): void
    {
        $this->usuario();

        $respuesta = $this->from(route('login'))
            ->followingRedirects()
            ->post(route('login'), ['usuario' => 'ana@nexo.test', 'password' => 'incorrecta']);

        $respuesta->assertSee('El correo o la contraseña no son correctos');
        $this->assertSame(1, substr_count($respuesta->getContent(), 'no son correctos'));
    }

    public function test_una_cuenta_inactiva_no_puede_entrar(): void
    {
        $this->usuario('inactivo');

        $this->post(route('login'), ['usuario' => 'ana@nexo.test', 'password' => 'Demo12345'])->assertSessionHasErrors('usuario');
        $this->assertGuest();
    }

    public function test_quien_ya_inicio_sesion_va_directo_al_panel(): void
    {
        $this->actingAs($this->usuario())->get(route('login'))->assertRedirect('/panel');
    }

    public function test_se_bloquea_el_acceso_tras_cinco_intentos_fallidos(): void
    {
        $this->usuario();

        foreach (range(1, 5) as $_) {
            $this->post(route('login'), ['usuario' => 'ana@nexo.test', 'password' => 'incorrecta'])
                ->assertSessionHasErrors('usuario');
        }

        // Con la contraseña correcta también se rechaza mientras dure el bloqueo.
        $this->post(route('login'), ['usuario' => 'ana@nexo.test', 'password' => 'Demo12345'])
            ->assertSessionHasErrors('usuario');
        $this->assertGuest();
    }
}
