<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaDatosDeReportes;
use Tests\TestCase;

/** CU8: los tres reportes servidos en JSON a la SPA. */
class ReportePanelApiTest extends TestCase
{
    use CreaDatosDeReportes, RefreshDatabase;

    private function administrador(): User
    {
        return User::factory()->create(['rol' => Rol::Administrador, 'estado' => 'activo']);
    }

    /** Un grupo con una matrícula inscrita: suficiente para que los tres reportes traigan filas. */
    private function conDatos(): void
    {
        $grupo = $this->grupo(['cohorte' => $this->cohorte()]);
        $this->inscribir($this->matricula(), $grupo);
    }

    public function test_los_tres_reportes_responden_con_su_estructura(): void
    {
        $admin = $this->administrador();

        foreach (['matriculas', 'ocupacion', 'catalogo'] as $reporte) {
            $this->actingAs($admin)->getJson("/api/panel/reportes/{$reporte}")
                ->assertOk()
                ->assertJsonStructure([
                    'titulo',
                    'generado_en',
                    'sin_datos',
                    'totales',
                    'secciones',
                ]);
        }
    }

    public function test_un_rango_de_fechas_invertido_responde_422_en_json(): void
    {
        $this->actingAs($this->administrador())
            ->getJson('/api/panel/reportes/matriculas?desde=2026-05-01&hasta=2026-01-01')
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_una_fecha_invalida_no_calcula_el_reporte(): void
    {
        $this->actingAs($this->administrador())
            ->getJson('/api/panel/reportes/matriculas?desde=no-es-fecha')
            ->assertStatus(422);
    }

    public function test_las_celdas_llegan_ya_formateadas(): void
    {
        $this->conDatos();

        $respuesta = $this->actingAs($this->administrador())
            ->getJson('/api/panel/reportes/ocupacion')
            ->assertOk();

        foreach ($respuesta->json('secciones') as $seccion) {
            foreach ($seccion['filas'] as $fila) {
                foreach ($fila as $celda) {
                    // El servidor ya aplicó Reporte::formatear: nada llega como número crudo.
                    $this->assertIsString($celda);
                }
            }
        }
    }

    public function test_el_reporte_trae_las_opciones_de_sus_filtros(): void
    {
        $this->conDatos();

        $this->actingAs($this->administrador())
            ->getJson('/api/panel/reportes/matriculas')
            ->assertOk()
            ->assertJsonStructure(['opciones' => ['programas', 'cohortes']]);
    }

    public function test_solo_el_administrador_ve_los_reportes(): void
    {
        $docente = User::factory()->create(['rol' => Rol::Docente, 'estado' => 'activo']);

        $this->actingAs($docente)->getJson('/api/panel/reportes/matriculas')->assertForbidden();
    }
}
