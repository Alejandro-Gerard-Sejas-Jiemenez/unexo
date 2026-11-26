<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportesAccesoTest extends TestCase
{
    use RefreshDatabase;

    private const RUTAS = [
        '/api/panel/reportes/matriculas',
        '/api/panel/reportes/matriculas/pdf',
        '/api/panel/reportes/matriculas/excel',
        '/api/panel/reportes/ocupacion',
        '/api/panel/reportes/ocupacion/pdf',
        '/api/panel/reportes/ocupacion/excel',
        '/api/panel/reportes/catalogo',
        '/api/panel/reportes/catalogo/pdf',
        '/api/panel/reportes/catalogo/excel',
    ];

    private function usuario(Rol $rol): User
    {
        return User::factory()->create(['rol' => $rol]);
    }

    public function test_docente_y_estudiante_reciben_acceso_denegado(): void
    {
        foreach ([Rol::Docente, Rol::Estudiante] as $rol) {
            $this->actingAs($this->usuario($rol));

            foreach (self::RUTAS as $ruta) {
                $this->getJson($ruta)->assertForbidden();
            }
        }
    }

    public function test_el_rol_no_autorizado_recibe_403_aunque_los_filtros_sean_invalidos(): void
    {
        $this->actingAs($this->usuario(Rol::Docente))
            ->getJson('/api/panel/reportes/matriculas?desde=2026-05-01&hasta=2026-01-01')
            ->assertForbidden();
    }

    public function test_el_visitante_recibe_401(): void
    {
        foreach (self::RUTAS as $ruta) {
            $this->getJson($ruta)->assertUnauthorized();
        }
    }

    public function test_toda_ruta_de_reportes_niega_el_acceso_a_docente_y_estudiante(): void
    {
        $rutas = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($ruta) => str_starts_with($ruta->uri(), 'api/panel/reportes/'));

        $this->assertNotEmpty($rutas);
        $this->assertCount(count(self::RUTAS), $rutas);

        foreach ([Rol::Docente, Rol::Estudiante] as $rol) {
            $this->actingAs($this->usuario($rol));

            foreach ($rutas as $ruta) {
                $this->assertContains('GET', $ruta->methods());
                $this->getJson('/'.$ruta->uri())->assertForbidden();
            }
        }
    }

    public function test_el_administrador_abre_los_tres_reportes_y_descarga_pdf_y_excel(): void
    {
        $this->actingAs($this->usuario(Rol::Administrador));

        foreach (self::RUTAS as $ruta) {
            $this->get($ruta)->assertOk();
        }
    }

    public function test_el_menu_del_administrador_muestra_reportes_y_otros_roles_no(): void
    {
        $menuAdmin = $this->actingAs($this->usuario(Rol::Administrador))
            ->getJson('/api/panel/menu')
            ->assertOk()
            ->json('menu');

        $this->assertContains('Reportes', array_column($menuAdmin, 'etiqueta'));

        foreach ([Rol::Docente, Rol::Estudiante] as $rol) {
            $menuOtro = $this->actingAs($this->usuario($rol))
                ->getJson('/api/panel/menu')
                ->assertOk()
                ->json('menu');

            $this->assertNotContains('Reportes', array_column($menuOtro, 'etiqueta'));
        }
    }

    public function test_las_rutas_de_reportes_solo_aceptan_get(): void
    {
        $this->actingAs($this->usuario(Rol::Administrador));

        foreach (self::RUTAS as $ruta) {
            $this->postJson($ruta)->assertStatus(405);
        }
    }
}
