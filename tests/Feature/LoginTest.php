<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
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

    public function test_la_pantalla_de_acceso_se_sirve_en_la_spa(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('id="app"', false);
    }

    public function test_se_puede_entrar_con_el_correo_personal_o_el_institucional(): void
    {
        $this->usuario();

        $this->postJson('/api/panel/login', ['usuario' => 'ana@nexo.test', 'password' => 'Demo12345'])
            ->assertOk()
            ->assertJsonPath('usuario.email', 'ana@nexo.test');

        $this->postJson('/api/panel/logout')->assertOk();

        $this->postJson('/api/panel/login', ['usuario' => 'ana@nexo.edu.test', 'password' => 'Demo12345'])
            ->assertOk()
            ->assertJsonPath('usuario.correo_institucional', 'ana@nexo.edu.test');
    }

    public function test_un_dato_incorrecto_muestra_un_solo_mensaje_claro(): void
    {
        $this->usuario();
        RateLimiter::clear('ana@nexo.test|127.0.0.1');

        $this->postJson('/api/panel/login', ['usuario' => 'ana@nexo.test', 'password' => 'incorrecta'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('usuario')
            ->assertJsonPath('errors.usuario.0', 'El correo o la contraseña no son correctos. Revisa los datos e inténtalo de nuevo.');
    }

    public function test_una_cuenta_inactiva_no_puede_entrar(): void
    {
        $this->usuario('inactivo');
        RateLimiter::clear('ana@nexo.test|127.0.0.1');

        $this->postJson('/api/panel/login', ['usuario' => 'ana@nexo.test', 'password' => 'Demo12345'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('usuario');
        $this->assertGuest();
    }

    public function test_quien_ya_inicio_sesion_recibe_su_perfil_en_me(): void
    {
        $this->actingAs($this->usuario())
            ->getJson('/api/panel/me')
            ->assertOk()
            ->assertJsonPath('usuario.email', 'ana@nexo.test');
    }

    public function test_se_bloquea_el_acceso_tras_cinco_intentos_fallidos(): void
    {
        $this->usuario();
        RateLimiter::clear('ana@nexo.test|127.0.0.1');

        foreach (range(1, 5) as $_) {
            $this->postJson('/api/panel/login', ['usuario' => 'ana@nexo.test', 'password' => 'incorrecta'])
                ->assertStatus(422)
                ->assertJsonValidationErrors('usuario');
        }

        $this->postJson('/api/panel/login', ['usuario' => 'ana@nexo.test', 'password' => 'Demo12345'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('usuario');
        $this->assertGuest();
    }
}
