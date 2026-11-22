<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\ClienteApi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * API que consume la SPA de React. Cubre lo que más riesgo tiene:
 * autenticación, separación del guard del CRM, matriz de acceso y validación.
 */
class PanelApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $extra = []): User
    {
        return User::factory()->create([...$extra, 'rol' => Rol::Administrador, 'estado' => 'activo']);
    }

    public function test_login_devuelve_el_usuario_autenticado(): void
    {
        $admin = $this->admin(['email' => 'admin@nexo.test', 'password' => 'Demo12345']);

        $this->postJson('/api/panel/login', ['usuario' => 'admin@nexo.test', 'password' => 'Demo12345'])
            ->assertOk()
            ->assertJsonPath('usuario.id', $admin->id)
            ->assertJsonPath('usuario.rol', 'administrador');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_login_con_credenciales_malas_responde_422_en_espanol(): void
    {
        $this->admin(['email' => 'admin@nexo.test', 'password' => 'Demo12345']);

        $this->postJson('/api/panel/login', ['usuario' => 'admin@nexo.test', 'password' => 'incorrecta'])
            ->assertStatus(422)
            ->assertJsonPath('errors.usuario.0', 'El correo o la contraseña no son correctos. Revisa los datos e inténtalo de nuevo.');

        $this->assertGuest();
    }

    public function test_login_bloquea_tras_cinco_intentos_fallidos(): void
    {
        RateLimiter::clear('bloqueado@nexo.test|127.0.0.1');
        $this->admin(['email' => 'bloqueado@nexo.test', 'password' => 'Demo12345']);

        foreach (range(1, 5) as $ignorado) {
            $this->postJson('/api/panel/login', ['usuario' => 'bloqueado@nexo.test', 'password' => 'mala'])
                ->assertStatus(422);
        }

        $this->postJson('/api/panel/login', ['usuario' => 'bloqueado@nexo.test', 'password' => 'Demo12345'])
            ->assertStatus(422)
            ->assertJsonPath('errors.usuario.0', fn (string $m) => str_contains($m, 'Demasiados intentos'));
    }

    public function test_usuario_inactivo_no_entra(): void
    {
        User::factory()->create([
            'rol' => Rol::Administrador,
            'email' => 'inactivo@nexo.test',
            'password' => 'Demo12345',
            'estado' => 'inactivo',
        ]);

        $this->postJson('/api/panel/login', ['usuario' => 'inactivo@nexo.test', 'password' => 'Demo12345'])
            ->assertStatus(422)
            ->assertJsonPath('errors.usuario.0', 'Tu cuenta está inactiva. Contacta al administrador.');
    }

    public function test_sin_sesion_la_api_del_panel_responde_401(): void
    {
        $this->getJson('/api/panel/me')->assertUnauthorized();
        $this->getJson('/api/panel/usuarios')->assertUnauthorized();
    }

    public function test_el_menu_lo_arma_el_servidor_segun_el_rol(): void
    {
        $docente = User::factory()->create(['rol' => Rol::Docente, 'estado' => 'activo']);

        $respuesta = $this->actingAs($docente)->getJson('/api/panel/menu')->assertOk();

        $etiquetas = collect($respuesta->json('menu'))->pluck('etiqueta')->all();

        $this->assertContains('Docencia', $etiquetas);
        $this->assertNotContains('Configuración y Seguridad', $etiquetas);
        $this->assertSame('/app', $respuesta->json('menu.0.ruta'));
    }

    public function test_un_docente_no_alcanza_el_modulo_de_usuarios(): void
    {
        $docente = User::factory()->create(['rol' => Rol::Docente, 'estado' => 'activo']);

        $this->actingAs($docente)->getJson('/api/panel/usuarios')->assertForbidden();
    }

    public function test_el_admin_lista_y_filtra_usuarios(): void
    {
        $admin = $this->admin(['nombres' => 'Ana', 'apellidos' => 'Zurita']);
        User::factory()->create(['rol' => Rol::Docente, 'nombres' => 'Bruno', 'apellidos' => 'Arce', 'estado' => 'activo']);

        $this->actingAs($admin)->getJson('/api/panel/usuarios')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->actingAs($admin)->getJson('/api/panel/usuarios?rol=docente')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombres', 'Bruno');

        $this->actingAs($admin)->getJson('/api/panel/usuarios?q=Zurita')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.apellidos', 'Zurita');
    }

    public function test_crear_usuario_valida_en_el_servidor_y_responde_en_espanol(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->postJson('/api/panel/usuarios', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['rol', 'nombres', 'apellidos', 'email', 'password']);

        $this->actingAs($admin)->postJson('/api/panel/usuarios', [
            'rol' => 'estudiante',
            'nombres' => 'Carla',
            'apellidos' => 'Ríos',
            'email' => 'carla@nexo.test',
            'password' => 'Demo12345',
        ])->assertCreated()->assertJsonPath('usuario.email', 'carla@nexo.test');

        $this->assertDatabaseHas('users', ['email' => 'carla@nexo.test', 'rol' => 'estudiante']);
    }

    public function test_editar_sin_contrasena_conserva_la_actual(): void
    {
        $admin = $this->admin();
        $usuario = User::factory()->create(['rol' => Rol::Estudiante, 'password' => 'Demo12345', 'estado' => 'activo']);
        $hashOriginal = $usuario->password;

        $this->actingAs($admin)->putJson("/api/panel/usuarios/{$usuario->id}", [
            'rol' => 'estudiante',
            'nombres' => 'Nuevo',
            'apellidos' => $usuario->apellidos,
            'email' => $usuario->email,
            'password' => '',
        ])->assertOk()->assertJsonPath('usuario.nombres', 'Nuevo');

        $this->assertSame($hashOriginal, $usuario->fresh()->password);
    }

    public function test_el_admin_no_puede_desactivarse_a_si_mismo(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->patchJson("/api/panel/usuarios/{$admin->id}/estado")
            ->assertStatus(422)
            ->assertJsonPath('errors.usuario.0', 'No puedes desactivar tu propia cuenta.');

        $this->assertTrue($admin->fresh()->estaActivo());
    }

    public function test_alternar_estado_de_otro_usuario(): void
    {
        $admin = $this->admin();
        $otro = User::factory()->create(['rol' => Rol::Docente, 'estado' => 'activo']);

        $this->actingAs($admin)->patchJson("/api/panel/usuarios/{$otro->id}/estado")
            ->assertOk()
            ->assertJsonPath('usuario.activo', false);

        $this->assertSame('inactivo', $otro->fresh()->estado);
    }

    public function test_un_usuario_del_panel_no_entra_a_la_api_del_crm(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->getJson('/api/v1/crm/catalogo')->assertForbidden();
    }

    public function test_un_cliente_api_no_entra_al_panel(): void
    {
        $cliente = ClienteApi::create(['nombre' => 'CRM de prueba']);
        $token = $cliente->createToken('crm')->plainTextToken;

        // El guard resuelve el token, pero 'rol:administrador' no encuentra un rol de negocio.
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/panel/usuarios')
            ->assertForbidden();
    }
}
