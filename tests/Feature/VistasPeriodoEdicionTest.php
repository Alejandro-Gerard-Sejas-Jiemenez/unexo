<?php

namespace Tests\Feature;

use App\Enums\EstadoComercial;
use App\Enums\Rol;
use App\Models\InscripcionGrupo;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaGruposConHorarios;
use Tests\TestCase;

class VistasPeriodoEdicionTest extends TestCase
{
    use CreaGruposConHorarios;
    use RefreshDatabase;

    private const PERIODO = '10/11/2026 – 20/12/2026';

    private function usuario(Rol $rol): User
    {
        return User::factory()->create(['rol' => $rol]);
    }

    public function test_el_detalle_de_la_edicion_muestra_el_periodo_de_la_edicion_en_sus_grupos(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-11-10', '2026-12-20');
        $this->crearGrupoConHorarios([], $edicion);

        $res = $this->actingAs($this->usuario(Rol::Administrador))
            ->getJson("/api/panel/cohortes/{$edicion->id}")
            ->assertOk();

        $this->assertSame(self::PERIODO, $res->json('data.grupos.0.periodo_texto'));
    }

    public function test_el_detalle_de_la_edicion_ordena_los_grupos_por_modulo_del_programa_y_luego_por_numero(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-11-10', '2026-12-20');
        $segundo = $this->crearGrupoConHorarios([], $edicion, ['nombre' => 'DEMO-Zeta', 'numero' => 1]);
        $primerA = $this->crearGrupoConHorarios([], $edicion, ['nombre' => 'DEMO-Alfa', 'numero' => 1]);
        $this->crearGrupoConHorarios([], $edicion, ['nombre' => 'DEMO-Beta', 'numero' => 2, 'modulo_id' => $primerA->modulo_id]);

        $edicion->programa->modulos()->attach($primerA->modulo_id, ['orden' => 1]);
        $edicion->programa->modulos()->attach($segundo->modulo_id, ['orden' => 2]);

        $res = $this->actingAs($this->usuario(Rol::Administrador))
            ->getJson("/api/panel/cohortes/{$edicion->id}")
            ->assertOk();

        $this->assertSame(['DEMO-Alfa', 'DEMO-Beta', 'DEMO-Zeta'], collect($res->json('data.grupos'))->pluck('nombre')->all());
    }

    public function test_catalogo_y_detalle_de_edicion_no_muestran_creditos_ni_nivel_ni_perfil(): void
    {
        $programa = Programa::create(['codigo' => 'DEMO-PC', 'nombre' => 'DEMO Programa', 'version' => '1.0', 'estado_comercial' => EstadoComercial::Abierto]);
        $modulo = Modulo::create(['codigo' => 'DEMO-MC', 'nombre' => 'DEMO Módulo', 'estado_comercial' => EstadoComercial::Abierto]);
        $programa->modulos()->attach($modulo->id, ['orden' => 1]);
        $admin = $this->usuario(Rol::Administrador);

        $catalogo = $this->actingAs($admin)->getJson('/api/panel/catalogo-publico')->assertOk()->getContent();
        foreach (['creditos', 'nivel_academico', 'perfil_egreso'] as $campo) {
            $this->assertStringNotContainsString("\"{$campo}\"", $catalogo);
        }

        $edicion = $this->crearEdicionConPeriodo('2026-11-10', '2026-12-20');
        $detalle = $this->getJson("/api/panel/cohortes/{$edicion->id}")->assertOk()->getContent();
        foreach (['creditos', 'nivel_academico', 'perfil_egreso'] as $campo) {
            $this->assertStringNotContainsString("\"{$campo}\"", $detalle);
        }
    }

    public function test_el_catalogo_muestra_el_inicio_del_grupo_suelto_y_busca_sin_nivel_academico(): void
    {
        $modulo = Modulo::create(['codigo' => 'DEMO-MS', 'nombre' => 'DEMO Suelto', 'estado_comercial' => EstadoComercial::Abierto]);
        $grupo = $this->crearGrupoConHorarios();
        $grupo->update(['modulo_id' => $modulo->id]);

        $res = $this->actingAs($this->usuario(Rol::Administrador))
            ->getJson('/api/panel/catalogo-publico?q=DEMO')
            ->assertOk();

        $this->assertSame('2026-03-01', $res->json('modulos.0.grupos_sueltos.0.fecha_inicio'));
    }

    public function test_los_paneles_y_el_detalle_de_matricula_muestran_el_periodo_de_un_grupo_de_edicion(): void
    {
        $edicion = $this->crearEdicionConPeriodo('2026-11-10', '2026-12-20');
        $grupo = $this->crearGrupoConHorarios([], $edicion);
        $docente = $grupo->docente;
        $docente->update(['rol' => Rol::Docente]);
        $estudiante = $this->usuario(Rol::Estudiante);
        $matricula = Matricula::create(['estudiante_id' => $estudiante->id, 'cohorte_id' => $edicion->id, 'fecha_matricula' => '2026-05-01']);
        InscripcionGrupo::create(['matricula_id' => $matricula->id, 'grupo_id' => $grupo->id]);

        $this->actingAs($docente)->getJson('/api/panel/inicio')->assertOk()->assertJsonPath('proximos.0.periodo_texto', self::PERIODO);
        $this->actingAs($docente)->getJson('/api/panel/mis-grupos')->assertOk()->assertJsonPath('data.0.periodo_texto', self::PERIODO);
        $this->actingAs($estudiante)->getJson('/api/panel/inicio')->assertOk()->assertJsonPath('proximos.0.periodo_texto', self::PERIODO);
        $this->actingAs($this->usuario(Rol::Administrador))
            ->getJson("/api/panel/matriculas/{$matricula->id}")
            ->assertOk()
            ->assertJsonPath('data.inscripciones.0.periodo_texto', self::PERIODO);
    }
}
