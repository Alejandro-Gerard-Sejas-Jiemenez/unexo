<?php

namespace Tests\Feature;

use App\Enums\EstadoComercial;
use App\Enums\EstadoPlanificacion;
use App\Enums\Rol;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\User;
use App\Services\MatriculaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuloFormularioTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['rol' => Rol::Administrador]);
    }

    private function datos(array $extra = []): array
    {
        return array_merge([
            'codigo' => 'DEMO-M1',
            'nombre' => 'DEMO-Excel',
            'duracion_horas' => 20,
            'estado_comercial' => EstadoComercial::Abierto->value,
        ], $extra);
    }

    /** Cohorte con un grupo de un módulo que tiene prerrequisito. */
    private function cohorteConModuloConPrerrequisito(): array
    {
        $programa = Programa::create(['codigo' => 'DEMO-P1', 'nombre' => 'DEMO-Diplomado', 'version' => '1.0', 'estado_comercial' => EstadoComercial::Abierto]);
        $docente = User::factory()->create(['rol' => Rol::Docente]);
        $cohorte = Cohorte::create(['programa_id' => $programa->id, 'nombre' => 'C1', 'fecha_inicio' => '2026-09-01', 'fecha_fin' => '2026-12-01', 'estado' => EstadoPlanificacion::Programado]);
        $modulo = Modulo::create(['codigo' => 'DEMO-M2', 'nombre' => 'DEMO-Python', 'estado_comercial' => EstadoComercial::Abierto, 'prerrequisito' => 'Lógica de programación básica']);
        $grupo = Grupo::create(['modulo_id' => $modulo->id, 'cohorte_id' => $cohorte->id, 'docente_id' => $docente->id, 'nombre' => 'A', 'fecha_inicio' => '2026-09-01', 'fecha_fin' => '2026-10-01', 'modalidad' => 'virtual', 'estado' => EstadoPlanificacion::Programado]);

        return [$cohorte, $grupo];
    }

    public function test_el_formulario_no_pide_creditos_ni_tipo_de_evaluacion(): void
    {
        // CA-01
        $respuesta = $this->actingAs($this->admin())->get(route('modulos.create'))->assertOk();

        $respuesta->assertDontSee('Créditos', false)
            ->assertDontSee('Tipo de Evaluación', false)
            ->assertDontSee('name="creditos"', false)
            ->assertDontSee('name="tipo_evaluacion"', false)
            ->assertSee('Prerrequisito (conocimientos previos)')
            ->assertSee('name="prerrequisito"', false)
            ->assertSee('0/500');
    }

    public function test_el_listado_de_modulos_y_el_detalle_de_programa_no_muestran_los_campos_retirados(): void
    {
        // CA-01
        $programa = Programa::create(['codigo' => 'DEMO-P1', 'nombre' => 'DEMO-Diplomado', 'version' => '1.0', 'estado_comercial' => EstadoComercial::Abierto]);
        $modulo = Modulo::create(['codigo' => 'DEMO-M1', 'nombre' => 'DEMO-Excel', 'estado_comercial' => EstadoComercial::Abierto]);
        $programa->modulos()->attach($modulo->id, ['orden' => 1]);
        $admin = $this->admin();

        foreach ([route('modulos.index'), route('programas.show', $programa)] as $url) {
            $this->actingAs($admin)->get($url)->assertOk()
                ->assertDontSee('Créditos', false)
                ->assertDontSee('créditos', false)
                ->assertDontSee('Tipo de evaluación', false)
                ->assertDontSee('Tipo de Evaluación', false);
        }
    }

    public function test_un_prerrequisito_de_mas_de_500_caracteres_se_rechaza_con_el_maximo(): void
    {
        // CA-02
        $this->actingAs($this->admin())->post(route('modulos.store'), $this->datos(['prerrequisito' => str_repeat('a', 501)]))
            ->assertSessionHasErrors(['prerrequisito' => 'El prerrequisito no puede tener más de 500 caracteres.']);

        $this->assertDatabaseCount('modulos', 0);
    }

    public function test_un_prerrequisito_de_exactamente_500_caracteres_se_acepta(): void
    {
        // CA-02
        $this->actingAs($this->admin())->post(route('modulos.store'), $this->datos(['prerrequisito' => str_repeat('a', 500)]))
            ->assertSessionHasNoErrors()->assertRedirect(route('modulos.index'));

        $this->assertDatabaseHas('modulos', ['codigo' => 'DEMO-M1']);
    }

    public function test_el_prerrequisito_es_opcional_y_no_se_guardan_creditos_ni_tipo(): void
    {
        // CA-01, CA-02
        $this->actingAs($this->admin())->post(route('modulos.store'), $this->datos(['creditos' => 5, 'tipo_evaluacion' => 'Examen']))
            ->assertSessionHasNoErrors();

        $modulo = Modulo::where('codigo', 'DEMO-M1')->firstOrFail();
        $this->assertNull($modulo->prerrequisito);
        $this->assertArrayNotHasKey('creditos', $modulo->getAttributes());
    }

    public function test_la_matricula_por_cohorte_se_acepta_con_prerrequisito(): void
    {
        // CA-03
        [$cohorte] = $this->cohorteConModuloConPrerrequisito();
        $estudiante = User::factory()->create(['rol' => Rol::Estudiante]);

        $this->actingAs($this->admin())->post(route('matriculas.store'), [
            'estudiante_id' => $estudiante->id,
            'tipo' => 'cohorte',
            'cohorte_id' => $cohorte->id,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $matricula = Matricula::where('estudiante_id', $estudiante->id)->firstOrFail();
        $this->assertSame(1, $matricula->inscripciones()->count());
    }

    public function test_el_servicio_de_matricula_acepta_un_grupo_con_prerrequisito(): void
    {
        // CA-03
        [$cohorte, $grupo] = $this->cohorteConModuloConPrerrequisito();
        $estudiante = User::factory()->create(['rol' => Rol::Estudiante]);

        $matricula = app(MatriculaService::class)->inscribirEnCohorte($estudiante, $cohorte);

        $this->assertSame($grupo->id, $matricula->inscripciones()->firstOrFail()->grupo_id);
    }

    public function test_editar_solo_el_prerrequisito_deja_iguales_los_demas_datos(): void
    {
        // CA-42
        $modulo = Modulo::create([
            'codigo' => 'DEMO-M1',
            'nombre' => 'DEMO-Excel',
            'duracion_horas' => 20,
            'descripcion' => 'DEMO descripción',
            'pdf_temario_url' => 'https://example.com/temario.pdf',
            'estado_comercial' => EstadoComercial::Abierto,
        ]);

        $this->actingAs($this->admin())->put(route('modulos.update', $modulo), $this->datos([
            'descripcion' => 'DEMO descripción',
            'pdf_temario_url' => 'https://example.com/temario.pdf',
            'prerrequisito' => 'Lógica de programación básica',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('modulos', [
            'id' => $modulo->id,
            'codigo' => 'DEMO-M1',
            'nombre' => 'DEMO-Excel',
            'duracion_horas' => 20,
            'descripcion' => 'DEMO descripción',
            'pdf_temario_url' => 'https://example.com/temario.pdf',
            'estado_comercial' => EstadoComercial::Abierto->value,
            'prerrequisito' => 'Lógica de programación básica',
        ]);
    }

    public function test_la_edicion_rechaza_un_prerrequisito_demasiado_largo(): void
    {
        // CA-02
        $modulo = Modulo::create(['codigo' => 'DEMO-M1', 'nombre' => 'DEMO-Excel', 'estado_comercial' => EstadoComercial::Abierto]);

        $this->actingAs($this->admin())->put(route('modulos.update', $modulo), $this->datos(['prerrequisito' => str_repeat('a', 501)]))
            ->assertSessionHasErrors('prerrequisito');
    }
}
