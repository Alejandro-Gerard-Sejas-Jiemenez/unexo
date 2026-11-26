<?php

namespace Tests\Feature;

use App\Enums\EstadoComercial;
use App\Enums\Rol;
use App\Models\ClienteApi;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\User;
use Database\Seeders\RbacMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacMenuApiTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuario(Rol $rol, string $estado = 'activo'): User
    {
        return User::factory()->create(['rol' => $rol, 'estado' => $estado]);
    }

    private function extraerRutas(array $menu): array
    {
        return collect($menu)->flatMap(function (array $nodo) {
            if (($nodo['tipo'] ?? '') === 'submenu') {
                return collect($nodo['items'] ?? [])->pluck('ruta');
            }

            return isset($nodo['ruta']) ? [$nodo['ruta']] : [];
        })->all();
    }

    public function test_cada_rol_recibe_su_menu_y_permisos_desde_base_de_datos(): void
    {
        $this->seed(RbacMenuSeeder::class);

        $admin = $this->actingAs($this->crearUsuario(Rol::Administrador))
            ->getJson('/api/panel/me')
            ->assertOk();

        $rutasAdmin = $this->extraerRutas($admin->json('menu'));
        $this->assertContains('/estadisticas', $rutasAdmin);
        $this->assertContains('/usuarios', $rutasAdmin);
        $this->assertContains('usuarios.gestionar', $admin->json('permisos'));

        $this->app['auth']->forgetGuards();
        $this->flushSession();

        $docente = $this->actingAs($this->crearUsuario(Rol::Docente))
            ->getJson('/api/panel/me')
            ->assertOk();

        $rutasDocente = $this->extraerRutas($docente->json('menu'));
        $this->assertContains('/mis-grupos', $rutasDocente);
        $this->assertContains('/calendario', $rutasDocente);
        $this->assertNotContains('/usuarios', $rutasDocente);

        $this->app['auth']->forgetGuards();
        $this->flushSession();

        $estudiante = $this->actingAs($this->crearUsuario(Rol::Estudiante))
            ->getJson('/api/panel/me')
            ->assertOk();

        $rutasEstudiante = $this->extraerRutas($estudiante->json('menu'));
        $this->assertContains('/mis-cursos', $rutasEstudiante);
        $this->assertContains('/pagos', $rutasEstudiante);
        $this->assertNotContains('/estadisticas', $rutasEstudiante);
    }

    public function test_un_cliente_api_no_accede_a_rutas_del_panel(): void
    {
        $cliente = ClienteApi::create(['nombre' => 'CRM externo']);
        $token = $cliente->createToken('crm')->plainTextToken;

        foreach (['/api/panel/me', '/api/panel/inicio', '/api/panel/catalogo/programas'] as $ruta) {
            $this->withHeader('Authorization', 'Bearer '.$token)
                ->getJson($ruta)
                ->assertForbidden();
        }
    }

    public function test_un_usuario_inactivo_es_rechazado_por_el_middleware_del_panel(): void
    {
        $inactivo = $this->crearUsuario(Rol::Estudiante, 'inactivo');

        $this->actingAs($inactivo)->getJson('/api/panel/inicio')->assertUnauthorized();
        $this->actingAs($inactivo)->getJson('/api/panel/me')->assertUnauthorized();
    }

    public function test_el_administrador_no_accede_al_calendario_personal_de_clases(): void
    {
        $this->actingAs($this->crearUsuario(Rol::Administrador))
            ->getJson('/api/panel/calendario')
            ->assertForbidden();
    }

    public function test_no_administradores_no_ven_programas_ni_modulos_en_borrador(): void
    {
        $programaBorrador = Programa::create([
            'codigo' => 'BOR-01',
            'nombre' => 'Programa Oculto',
            'version' => '1.0',
            'estado_comercial' => EstadoComercial::Borrador,
        ]);
        $moduloBorrador = Modulo::create([
            'codigo' => 'MOD-BOR',
            'nombre' => 'Módulo Oculto',
            'estado_comercial' => EstadoComercial::Borrador,
        ]);

        $estudiante = $this->crearUsuario(Rol::Estudiante);

        $this->actingAs($estudiante)
            ->getJson('/api/panel/catalogo/programas')
            ->assertOk()
            ->assertJsonMissing(['nombre' => 'Programa Oculto']);

        $this->actingAs($estudiante)
            ->getJson("/api/panel/catalogo/programas/{$programaBorrador->id}")
            ->assertNotFound();

        $this->actingAs($estudiante)
            ->getJson("/api/panel/catalogo/modulos/{$moduloBorrador->id}")
            ->assertNotFound();
    }
}
