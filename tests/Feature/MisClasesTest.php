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
            'enlace_videoconferencia' => 'https://meet.example.test/'.strtolower(str_replace(' ', '-', $nombreModulo)),
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

        $res = $this->actingAs($andres)->getJson('/api/panel/mis-cursos')->assertOk();
        $nombres = collect($res->json('data'))->pluck('grupo.modulo.nombre')->all();

        $this->assertContains('Excel Avanzado', $nombres);
        $this->assertNotContains('Contabilidad Secreta', $nombres);
    }

    public function test_el_enlace_de_la_clase_solo_aparece_con_matricula_pagada(): void
    {
        $docente = $this->usuario(Rol::Docente);
        $pagado = $this->usuario(Rol::Estudiante);
        $pendiente = $this->usuario(Rol::Estudiante);
        $grupo = $this->grupo($docente, 'Power BI');
        $this->inscribir($pagado, $grupo, EstadoMatricula::Activo);
        $this->inscribir($pendiente, $grupo, EstadoMatricula::Pendiente);

        $resPagado = $this->actingAs($pagado)->getJson('/api/panel/mis-cursos')->assertOk();
        $this->assertNotNull($resPagado->json('data.0.grupo.enlace_videoconferencia'));

        $resPendiente = $this->actingAs($pendiente)->getJson('/api/panel/mis-cursos')->assertOk();
        $this->assertNull($resPendiente->json('data.0.grupo.enlace_videoconferencia'));
        $this->assertSame('pendiente', $resPendiente->json('data.0.matricula_estado'));
    }

    public function test_el_docente_ve_sus_grupos_y_sus_estudiantes(): void
    {
        $carlos = $this->usuario(Rol::Docente);
        $lucia = $this->usuario(Rol::Docente);
        $estudiante = $this->usuario(Rol::Estudiante, 'Daniela');
        $grupoCarlos = $this->grupo($carlos, 'Excel Basico');
        $this->grupo($lucia, 'Marketing Digital');
        $this->inscribir($estudiante, $grupoCarlos);

        $resLista = $this->actingAs($carlos)->getJson('/api/panel/mis-grupos')->assertOk();
        $nombres = collect($resLista->json('data'))->pluck('modulo.nombre')->all();
        $this->assertContains('Excel Basico', $nombres);
        $this->assertNotContains('Marketing Digital', $nombres);

        $resDetalle = $this->actingAs($carlos)->getJson("/api/panel/mis-grupos/{$grupoCarlos->id}")->assertOk();
        $estudiantes = collect($resDetalle->json('estudiantes'))->pluck('nombre')->implode(' ');
        $this->assertStringContainsString('Daniela', $estudiantes);
    }

    public function test_un_docente_no_puede_ver_el_grupo_de_otro(): void
    {
        $carlos = $this->usuario(Rol::Docente);
        $lucia = $this->usuario(Rol::Docente);
        $grupoLucia = $this->grupo($lucia, 'Marketing Digital');

        $this->actingAs($carlos)->getJson("/api/panel/mis-grupos/{$grupoLucia->id}")->assertForbidden();
    }

    public function test_cada_rol_solo_entra_a_su_pantalla(): void
    {
        $this->actingAs($this->usuario(Rol::Estudiante))->getJson('/api/panel/mis-grupos')->assertForbidden();
        $this->actingAs($this->usuario(Rol::Docente))->getJson('/api/panel/mis-cursos')->assertForbidden();
        $this->actingAs($this->usuario(Rol::Docente))->postJson('/api/panel/catalogo/programas', [])->assertForbidden();
    }

    public function test_la_busqueda_filtra_los_cursos(): void
    {
        $docente = $this->usuario(Rol::Docente);
        $estudiante = $this->usuario(Rol::Estudiante);
        $this->inscribir($estudiante, $this->grupo($docente, 'Excel Avanzado'));
        $this->inscribir($estudiante, $this->grupo($docente, 'Liderazgo'));

        $res = $this->actingAs($estudiante)->getJson('/api/panel/mis-cursos?q=excel')->assertOk();
        $nombres = collect($res->json('data'))->pluck('grupo.modulo.nombre')->all();

        $this->assertContains('Excel Avanzado', $nombres);
        $this->assertNotContains('Liderazgo', $nombres);
    }

    public function test_una_busqueda_con_codigo_html_no_se_ejecuta(): void
    {
        $estudiante = $this->usuario(Rol::Estudiante);

        $this->actingAs($estudiante)
            ->getJson('/api/panel/mis-cursos?q='.urlencode('<script>alert(1)</script>'))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
