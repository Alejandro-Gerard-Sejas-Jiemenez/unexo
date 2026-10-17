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
        'reportes.matriculas',
        'reportes.matriculas.pdf',
        'reportes.matriculas.excel',
        'reportes.ocupacion',
        'reportes.ocupacion.pdf',
        'reportes.ocupacion.excel',
        'reportes.catalogo',
        'reportes.catalogo.pdf',
        'reportes.catalogo.excel',
    ];

    private const TEXTO_DENEGADO = 'No tienes permiso para ver los reportes.';

    private function usuario(Rol $rol): User
    {
        return User::factory()->create(['rol' => $rol]);
    }

    // RF-02 / CA-02, RF-26 / CA-26
    public function test_docente_y_estudiante_reciben_acceso_denegado_con_el_texto_de_reportes(): void
    {
        foreach ([Rol::Docente, Rol::Estudiante] as $rol) {
            $this->actingAs($this->usuario($rol));

            foreach (self::RUTAS as $ruta) {
                $this->get(route($ruta))
                    ->assertForbidden()
                    ->assertSee(self::TEXTO_DENEGADO)
                    ->assertDontSee('Total de matrículas');
            }
        }
    }

    // RF-02: la autorización va antes que la validación, un filtro inválido no la salta
    public function test_el_rol_no_autorizado_recibe_403_aunque_los_filtros_sean_invalidos(): void
    {
        $this->actingAs($this->usuario(Rol::Docente))
            ->get(route('reportes.matriculas', ['desde' => '2026-05-01', 'hasta' => '2026-01-01']))
            ->assertForbidden();
    }

    // RF-03 / CA-03
    public function test_el_visitante_es_llevado_al_inicio_de_sesion(): void
    {
        foreach (self::RUTAS as $ruta) {
            $this->get(route($ruta))->assertRedirect(route('login'));
        }
    }

    // T-22: ninguna ruta reportes.* puede quedar sin chequeo de rol (efecto de autorizar en el Form Request)
    public function test_toda_ruta_de_reportes_niega_el_acceso_a_docente_y_estudiante(): void
    {
        $rutas = collect(Route::getRoutes()->getRoutesByName())
            ->filter(fn ($ruta, $nombre) => str_starts_with($nombre, 'reportes.'));

        $this->assertNotEmpty($rutas);
        $this->assertEqualsCanonicalizing(self::RUTAS, $rutas->keys()->all(), 'Hay rutas de reportes no cubiertas por esta prueba.');

        foreach ([Rol::Docente, Rol::Estudiante] as $rol) {
            $this->actingAs($this->usuario($rol));

            foreach ($rutas as $nombre => $ruta) {
                $this->assertContains('GET', $ruta->methods());
                $this->get('/'.$ruta->uri())->assertForbidden();
            }
        }
    }

    // RF-01 / CA-01
    public function test_el_administrador_abre_los_tres_reportes_y_descarga_pdf_y_excel(): void
    {
        $this->actingAs($this->usuario(Rol::Administrador));

        foreach (self::RUTAS as $ruta) {
            $this->get(route($ruta))->assertOk();
        }
    }

    private function enlaceDelMenu(string $html): ?string
    {
        preg_match('#<a href="([^"]*)" title="Reportes"([^>]*)>#', $html, $coincidencia);

        return $coincidencia ? $coincidencia[0] : null;
    }

    // RF-20 / CA-20
    public function test_el_menu_del_administrador_muestra_reportes_en_cualquier_pantalla(): void
    {
        $this->actingAs($this->usuario(Rol::Administrador));

        foreach (['panel', 'programas.index', 'matriculas.index', 'catalogo'] as $ruta) {
            $enlace = $this->enlaceDelMenu($this->get(route($ruta))->assertOk()->getContent());

            $this->assertNotNull($enlace, "El menú no muestra Reportes en {$ruta}.");
            $this->assertStringContainsString('href="'.route('reportes.matriculas').'"', $enlace);
            $this->assertStringNotContainsString('aria-current', $enlace);
        }
    }

    // RF-21 / CA-21
    public function test_docente_y_estudiante_no_ven_reportes_en_su_menu(): void
    {
        foreach ([Rol::Docente, Rol::Estudiante] as $rol) {
            $this->actingAs($this->usuario($rol));

            $contenido = $this->get(route('panel'))->assertOk()->getContent();

            $this->assertNull($this->enlaceDelMenu($contenido));
            $this->assertStringNotContainsString(route('reportes.matriculas'), $contenido);
        }
    }

    // RF-22 / CA-22
    public function test_reportes_es_la_seccion_activa_y_permite_pasar_a_los_otros_dos(): void
    {
        $this->actingAs($this->usuario(Rol::Administrador));

        foreach (['reportes.matriculas', 'reportes.ocupacion', 'reportes.catalogo'] as $ruta) {
            $respuesta = $this->get(route($ruta))->assertOk();

            $this->assertStringContainsString('aria-current="page"', $this->enlaceDelMenu($respuesta->getContent()));
            foreach (['reportes.matriculas', 'reportes.ocupacion', 'reportes.catalogo'] as $destino) {
                $respuesta->assertSee('href="'.route($destino).'"', false);
            }
        }
    }

    public function test_las_rutas_de_reportes_solo_aceptan_get(): void
    {
        $this->actingAs($this->usuario(Rol::Administrador));

        foreach (self::RUTAS as $ruta) {
            $this->post(route($ruta))->assertStatus(405);
        }
    }
}
