<?php

namespace Tests\Feature;

use App\Enums\EstadoComercial;
use App\Enums\EstadoMatricula;
use App\Enums\EstadoPlanificacion;
use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\InscripcionGrupo;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanificacionCohorteTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['rol' => Rol::Administrador]);
    }

    private function docente(): User
    {
        return User::factory()->create(['rol' => Rol::Docente, 'estado' => 'activo']);
    }

    private function programaConModulos(int $cantidad = 3): array
    {
        $codigo = 'P-'.uniqid();
        $programa = Programa::create([
            'codigo' => $codigo,
            'nombre' => 'Maestría en Sistemas '.$codigo,
            'version' => '1.0',
            'estado_comercial' => EstadoComercial::Abierto,
        ]);

        $modulos = [];
        for ($i = 1; $i <= $cantidad; $i++) {
            $m = Modulo::create([
                'codigo' => "MOD-{$codigo}-$i",
                'nombre' => "Módulo $i de Especialidad",
            ]);
            $programa->modulos()->attach($m->id, ['orden' => $i]);
            $modulos[] = $m;
        }

        return [$programa, $modulos];
    }

    public function test_apertura_asistida_genera_grupos_para_todos_los_modulos_del_programa(): void
    {
        $docente = $this->docente();
        [$programa, $modulos] = $this->programaConModulos(3);

        $cohorte = Cohorte::create([
            'programa_id' => $programa->id,
            'nombre' => 'Cohorte 2026-I',
            'fecha_inicio' => Carbon::parse('2026-03-01'),
            'fecha_fin' => Carbon::parse('2026-05-31'),
            'estado' => EstadoPlanificacion::Programado,
        ]);

        $this->actingAs($this->admin())
            ->post(route('cohortes.aperturar-grupos', $cohorte), [
                'docente_id' => $docente->id,
                'modalidad' => Modalidad::Virtual->value,
                'cupo_minimo' => 20,
            ])
            ->assertRedirect(route('cohortes.show', $cohorte))
            ->assertSessionHas('ok');

        $this->assertCount(3, $cohorte->grupos);
        $this->assertTrue($cohorte->tieneTodosLosModulosAperturados());

        $grupos = $cohorte->grupos()->orderBy('fecha_inicio')->get();

        // Verificar que cada módulo del programa tiene su grupo con cupo mínimo 20
        foreach ($modulos as $index => $modulo) {
            $grupo = $grupos[$index];
            $this->assertEquals($modulo->id, $grupo->modulo_id);
            $this->assertEquals(20, $grupo->cupo_minimo);
            $this->assertEquals($docente->id, $grupo->docente_id);
            // Las fechas deben estar dentro del rango de la cohorte
            $this->assertTrue($grupo->fecha_inicio >= $cohorte->fecha_inicio);
            $this->assertTrue($grupo->fecha_fin <= $cohorte->fecha_fin);
            $this->assertTrue($grupo->fecha_fin >= $grupo->fecha_inicio);
        }

        // Si se llama de nuevo, no duplica módulos
        $this->actingAs($this->admin())
            ->post(route('cohortes.aperturar-grupos', $cohorte))
            ->assertRedirect(route('cohortes.show', $cohorte))
            ->assertSessionHas('info');

        $this->assertEquals(3, $cohorte->grupos()->count());
    }

    public function test_apertura_asistida_falla_si_el_programa_no_tiene_modulos(): void
    {
        $this->docente();
        $programaVacio = Programa::create([
            'codigo' => 'PROG-VAC-'.uniqid(),
            'nombre' => 'Programa Sin Módulos',
            'version' => '1.0',
            'estado_comercial' => EstadoComercial::Abierto,
        ]);

        $cohorte = Cohorte::create([
            'programa_id' => $programaVacio->id,
            'nombre' => 'Cohorte Vacía',
            'fecha_inicio' => Carbon::parse('2026-03-01'),
            'fecha_fin' => Carbon::parse('2026-05-31'),
            'estado' => EstadoPlanificacion::Programado,
        ]);

        $this->actingAs($this->admin())
            ->from(route('cohortes.show', $cohorte))
            ->post(route('cohortes.aperturar-grupos', $cohorte))
            ->assertRedirect(route('cohortes.show', $cohorte))
            ->assertSessionHasErrors('cohorte');

        $this->assertEquals(0, $cohorte->grupos()->count());
    }

    public function test_cambio_de_estado_de_la_cohorte(): void
    {
        [$programa] = $this->programaConModulos(1);
        $cohorte = Cohorte::create([
            'programa_id' => $programa->id,
            'nombre' => 'Cohorte Test',
            'fecha_inicio' => Carbon::parse('2026-03-01'),
            'fecha_fin' => Carbon::parse('2026-05-31'),
            'estado' => EstadoPlanificacion::Programado,
        ]);

        $this->actingAs($this->admin())
            ->patch(route('cohortes.estado', $cohorte), [
                'estado' => EstadoPlanificacion::EnCurso->value,
            ])
            ->assertRedirect(route('cohortes.show', $cohorte))
            ->assertSessionHas('ok');

        $cohorte->refresh();
        $this->assertEquals(EstadoPlanificacion::EnCurso, $cohorte->estado);
    }

    public function test_validacion_modulo_debe_pertenecer_al_programa_de_la_cohorte(): void
    {
        $docente = $this->docente();
        [$programaCohorte] = $this->programaConModulos(1);
        [$otroPrograma, $otrosModulos] = $this->programaConModulos(1);

        $cohorte = Cohorte::create([
            'programa_id' => $programaCohorte->id,
            'nombre' => 'Cohorte Beta',
            'fecha_inicio' => Carbon::parse('2026-03-01'),
            'fecha_fin' => Carbon::parse('2026-05-31'),
            'estado' => EstadoPlanificacion::Programado,
        ]);

        $moduloAjeno = $otrosModulos[0];

        $this->actingAs($this->admin())
            ->post(route('grupos.store'), [
                'modulo_id' => $moduloAjeno->id,
                'cohorte_id' => $cohorte->id,
                'docente_id' => $docente->id,
                'nombre' => 'Grupo G1',
                'fecha_inicio' => '2026-03-05',
                'fecha_fin' => '2026-03-25',
                'modalidad' => Modalidad::Virtual->value,
                'cupo_minimo' => 20,
                'estado' => EstadoPlanificacion::Programado->value,
            ])
            ->assertSessionHasErrors('modulo_id');
    }

    public function test_validacion_fechas_del_grupo_no_pueden_desbordar_la_cohorte(): void
    {
        $docente = $this->docente();
        [$programa, $modulos] = $this->programaConModulos(1);

        $cohorte = Cohorte::create([
            'programa_id' => $programa->id,
            'nombre' => 'Cohorte Gamma',
            'fecha_inicio' => Carbon::parse('2026-03-01'),
            'fecha_fin' => Carbon::parse('2026-05-31'),
            'estado' => EstadoPlanificacion::Programado,
        ]);

        // Fecha anterior al inicio de la cohorte
        $this->actingAs($this->admin())
            ->post(route('grupos.store'), [
                'modulo_id' => $modulos[0]->id,
                'cohorte_id' => $cohorte->id,
                'docente_id' => $docente->id,
                'nombre' => 'Grupo G1',
                'fecha_inicio' => '2026-02-15',
                'fecha_fin' => '2026-03-15',
                'modalidad' => Modalidad::Virtual->value,
                'cupo_minimo' => 20,
                'estado' => EstadoPlanificacion::Programado->value,
            ])
            ->assertSessionHasErrors('fecha_inicio');

        // Fecha posterior al fin de la cohorte
        $this->actingAs($this->admin())
            ->post(route('grupos.store'), [
                'modulo_id' => $modulos[0]->id,
                'cohorte_id' => $cohorte->id,
                'docente_id' => $docente->id,
                'nombre' => 'Grupo G2',
                'fecha_inicio' => '2026-04-01',
                'fecha_fin' => '2026-06-15',
                'modalidad' => Modalidad::Virtual->value,
                'cupo_minimo' => 20,
                'estado' => EstadoPlanificacion::Programado->value,
            ])
            ->assertSessionHasErrors('fecha_fin');
    }

    public function test_creacion_de_grupo_modulo_suelto_sin_cohorte(): void
    {
        $docente = $this->docente();
        $modulo = Modulo::create(['codigo' => 'MOD-FREE-'.uniqid(), 'nombre' => 'Taller Libre']);

        $this->actingAs($this->admin())
            ->post(route('grupos.store'), [
                'modulo_id' => $modulo->id,
                'cohorte_id' => null,
                'docente_id' => $docente->id,
                'nombre' => 'Taller Abierto 1',
                'fecha_inicio' => '2026-04-01',
                'fecha_fin' => '2026-04-30',
                'modalidad' => Modalidad::Virtual->value,
                'cupo_minimo' => 20,
                'estado' => EstadoPlanificacion::Programado->value,
            ])
            ->assertRedirect(route('grupos.index'))
            ->assertSessionHas('ok');

        $grupo = Grupo::where('nombre', 'Taller Abierto 1')->first();
        $this->assertNotNull($grupo);
        $this->assertNull($grupo->cohorte_id);

        $this->assertEquals(1, Grupo::sueltos()->count());
        $this->assertEquals(0, Grupo::deCohorte()->count());
    }

    public function test_calculo_y_estado_del_quorum_minimo_20(): void
    {
        $docente = $this->docente();
        $modulo = Modulo::create(['codigo' => 'MOD-Q-'.uniqid(), 'nombre' => 'Módulo Quórum']);

        $grupo = Grupo::create([
            'modulo_id' => $modulo->id,
            'docente_id' => $docente->id,
            'nombre' => 'Grupo Quorum',
            'fecha_inicio' => '2026-04-01',
            'fecha_fin' => '2026-04-30',
            'modalidad' => Modalidad::Virtual,
            'cupo_minimo' => 20,
            'estado' => EstadoPlanificacion::Programado,
        ]);

        $quorumInicial = $grupo->quorum;
        $this->assertEquals(0, $quorumInicial['inscritos']);
        $this->assertEquals(20, $quorumInicial['minimo']);
        $this->assertFalse($quorumInicial['alcanzado']);
        $this->assertEquals(20, $quorumInicial['restante']);
        $this->assertEquals(0, $quorumInicial['porcentaje']);

        // Simular 20 estudiantes inscritos
        for ($i = 1; $i <= 20; $i++) {
            $estudiante = User::factory()->create(['rol' => Rol::Estudiante]);
            $matricula = Matricula::create([
                'estudiante_id' => $estudiante->id,
                'estado' => EstadoMatricula::Activo,
                'fecha_matricula' => now()->toDateString(),
            ]);
            InscripcionGrupo::create([
                'matricula_id' => $matricula->id,
                'grupo_id' => $grupo->id,
            ]);
        }

        $grupo->loadCount('inscripciones');
        $quorumCompleto = $grupo->quorum;
        $this->assertEquals(20, $quorumCompleto['inscritos']);
        $this->assertTrue($quorumCompleto['alcanzado']);
        $this->assertEquals(0, $quorumCompleto['restante']);
        $this->assertEquals(100, $quorumCompleto['porcentaje']);
    }

    public function test_filtros_de_tipo_en_listado_de_grupos(): void
    {
        $docente = $this->docente();
        [$programa, $modulos] = $this->programaConModulos(2);
        $cohorte = Cohorte::create([
            'programa_id' => $programa->id,
            'nombre' => 'Cohorte Filtro',
            'fecha_inicio' => Carbon::parse('2026-03-01'),
            'fecha_fin' => Carbon::parse('2026-05-31'),
            'estado' => EstadoPlanificacion::Programado,
        ]);

        // Grupo de cohorte
        Grupo::create([
            'modulo_id' => $modulos[0]->id,
            'cohorte_id' => $cohorte->id,
            'docente_id' => $docente->id,
            'nombre' => 'Grupo Cohorte',
            'fecha_inicio' => '2026-03-05',
            'fecha_fin' => '2026-03-25',
            'modalidad' => Modalidad::Virtual,
            'cupo_minimo' => 20,
            'estado' => EstadoPlanificacion::Programado,
        ]);

        // Grupo suelto
        Grupo::create([
            'modulo_id' => $modulos[1]->id,
            'cohorte_id' => null,
            'docente_id' => $docente->id,
            'nombre' => 'Grupo Suelto',
            'fecha_inicio' => '2026-03-05',
            'fecha_fin' => '2026-03-25',
            'modalidad' => Modalidad::Virtual,
            'cupo_minimo' => 20,
            'estado' => EstadoPlanificacion::Programado,
        ]);

        // Filtrar por cohorte
        $responseCohorte = $this->actingAs($this->admin())
            ->get(route('grupos.index', ['tipo' => 'cohorte']))
            ->assertOk();
        $responseCohorte->assertSee('Grupo Cohorte');
        $responseCohorte->assertDontSee('Grupo Suelto');

        // Filtrar por suelto
        $responseSuelto = $this->actingAs($this->admin())
            ->get(route('grupos.index', ['tipo' => 'suelto']))
            ->assertOk();
        $responseSuelto->assertSee('Grupo Suelto');
        $responseSuelto->assertDontSee('Grupo Cohorte');
    }
}
