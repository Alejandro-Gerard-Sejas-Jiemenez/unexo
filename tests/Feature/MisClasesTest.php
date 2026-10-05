<?php

namespace Tests\Feature;

use App\Enums\EstadoMatricula;
use App\Enums\EstadoPlanificacion;
use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Models\Grupo;
use App\Models\InscripcionGrupo;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MisClasesTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(Rol $rol, string $nombres = 'Persona'): User
    {
        return User::factory()->create(['rol' => $rol, 'nombres' => $nombres]);
    }

    private function grupo(User $docente, string $nombreModulo): Grupo
    {
        $modulo = Modulo::create([
            'codigo' => 'T-'.strtoupper(substr(md5($nombreModulo), 0, 6)),
            'nombre' => $nombreModulo,
            'estado_comercial' => 'abierto',
        ]);

        return Grupo::create([
            'modulo_id' => $modulo->id,
            'docente_id' => $docente->id,
            'nombre' => 'Grupo A',
            'fecha_inicio' => today()->subDays(5),
            'fecha_fin' => today()->addDays(20),
            'modalidad' => Modalidad::Virtual,
            'enlace_videoconferencia' => 'https://meet.example.test/'.strtolower($nombreModulo),
            'estado' => EstadoPlanificacion::Programado,
        ]);
    }

    private function inscribir(User $estudiante, Grupo $grupo, EstadoMatricula $estado = EstadoMatricula::Activo): void
    {
        $matricula = Matricula::create([
            'estudiante_id' => $estudiante->id,
            'estado' => $estado,
            'fecha_matricula' => today(),
        ]);
        InscripcionGrupo::create(['matricula_id' => $matricula->id, 'grupo_id' => $grupo->id]);
    }

    public function test_el_estudiante_ve_solo_sus_cursos(): void
    {
        $docente = $this->usuario(Rol::Docente);
        $andres = $this->usuario(Rol::Estudiante);
        $otro = $this->usuario(Rol::Estudiante);
        $this->inscribir($andres, $this->grupo($docente, 'Excel Avanzado'));
        $this->inscribir($otro, $this->grupo($docente, 'Contabilidad Secreta'));

        $this->actingAs($andres)->get(route('mis-cursos'))
            ->assertOk()
            ->assertSee('Excel Avanzado')
            ->assertDontSee('Contabilidad Secreta');
    }

    public function test_el_enlace_de_la_clase_solo_aparece_con_matricula_pagada(): void
    {
        $docente = $this->usuario(Rol::Docente);
        $pagado = $this->usuario(Rol::Estudiante);
        $pendiente = $this->usuario(Rol::Estudiante);
        $grupo = $this->grupo($docente, 'Power BI');
        $this->inscribir($pagado, $grupo, EstadoMatricula::Activo);
        $this->inscribir($pendiente, $grupo, EstadoMatricula::Pendiente);

        $this->actingAs($pagado)->get(route('mis-cursos'))->assertSee('Entrar a la clase');
        $this->actingAs($pendiente)->get(route('mis-cursos'))
            ->assertDontSee('Entrar a la clase')
            ->assertSee('cuando se confirme tu pago');
    }

    public function test_el_docente_ve_sus_grupos_y_sus_estudiantes(): void
    {
        $carlos = $this->usuario(Rol::Docente);
        $lucia = $this->usuario(Rol::Docente);
        $estudiante = $this->usuario(Rol::Estudiante, 'Daniela');
        $grupoCarlos = $this->grupo($carlos, 'Excel Basico');
        $this->grupo($lucia, 'Marketing Digital');
        $this->inscribir($estudiante, $grupoCarlos);

        $this->actingAs($carlos)->get(route('mis-grupos.index'))
            ->assertOk()
            ->assertSee('Excel Basico')
            ->assertDontSee('Marketing Digital');

        $this->actingAs($carlos)->get(route('mis-grupos.show', $grupoCarlos))
            ->assertOk()
            ->assertSee('Daniela');
    }

    public function test_un_docente_no_puede_ver_el_grupo_de_otro(): void
    {
        $carlos = $this->usuario(Rol::Docente);
        $lucia = $this->usuario(Rol::Docente);
        $grupoLucia = $this->grupo($lucia, 'Marketing Digital');

        $this->actingAs($carlos)->get(route('mis-grupos.show', $grupoLucia))->assertForbidden();
    }

    public function test_cada_rol_solo_entra_a_su_pantalla(): void
    {
        $this->actingAs($this->usuario(Rol::Estudiante))->get(route('mis-grupos.index'))->assertForbidden();
        $this->actingAs($this->usuario(Rol::Docente))->get(route('mis-cursos'))->assertForbidden();
        $this->actingAs($this->usuario(Rol::Docente))->get(route('programas.index'))->assertForbidden();
    }

    public function test_la_busqueda_filtra_los_cursos(): void
    {
        $docente = $this->usuario(Rol::Docente);
        $estudiante = $this->usuario(Rol::Estudiante);
        $this->inscribir($estudiante, $this->grupo($docente, 'Excel Avanzado'));
        $this->inscribir($estudiante, $this->grupo($docente, 'Liderazgo'));

        $this->actingAs($estudiante)->get(route('mis-cursos', ['q' => 'excel']))
            ->assertSee('Excel Avanzado')
            ->assertDontSee('Liderazgo');
    }

    public function test_una_busqueda_con_codigo_html_no_se_ejecuta(): void
    {
        $estudiante = $this->usuario(Rol::Estudiante);

        $this->actingAs($estudiante)->get(route('mis-cursos', ['q' => '<script>alert(1)</script>']))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}
