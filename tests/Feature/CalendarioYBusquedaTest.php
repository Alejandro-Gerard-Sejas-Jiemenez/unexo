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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarioYBusquedaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_calendario_muestra_las_clases_del_estudiante(): void
    {
        $docente = User::factory()->create(['rol' => Rol::Docente]);
        $estudiante = User::factory()->create(['rol' => Rol::Estudiante]);
        $modulo = Modulo::create(['codigo' => 'C-1', 'nombre' => 'Redes Avanzadas', 'estado_comercial' => 'abierto']);
        $grupo = Grupo::create([
            'modulo_id' => $modulo->id,
            'docente_id' => $docente->id,
            'nombre' => 'Grupo A',
            'fecha_inicio' => now()->startOfMonth()->addDays(3),
            'fecha_fin' => now()->startOfMonth()->addDays(20),
            'modalidad' => Modalidad::Virtual,
            'estado' => EstadoPlanificacion::Programado,
        ]);
        $matricula = Matricula::create(['estudiante_id' => $estudiante->id, 'estado' => EstadoMatricula::Activo, 'fecha_matricula' => now()]);
        InscripcionGrupo::create(['matricula_id' => $matricula->id, 'grupo_id' => $grupo->id]);

        $this->actingAs($estudiante)->get(route('calendario'))
            ->assertOk()
            ->assertSee('Inicia: Redes Avanzadas · Grupo A', false)
            ->assertSee('Termina: Redes Avanzadas · Grupo A', false);
    }

    // CA-40: un grupo de edición aparece en el calendario con las fechas de su edición
    public function test_el_calendario_usa_el_periodo_de_la_edicion_para_un_grupo_de_edicion(): void
    {
        $docente = User::factory()->create(['rol' => Rol::Docente]);
        $estudiante = User::factory()->create(['rol' => Rol::Estudiante]);
        $modulo = Modulo::create(['codigo' => 'C-2', 'nombre' => 'Seguridad', 'estado_comercial' => 'abierto']);
        $programa = Programa::create(['codigo' => 'P-2', 'nombre' => 'Diplomado', 'version' => '1.0', 'estado_comercial' => EstadoComercial::Abierto]);
        $cohorte = Cohorte::create([
            'programa_id' => $programa->id,
            'nombre' => 'Edición 1',
            'fecha_inicio' => now()->startOfMonth()->addDays(4),
            'fecha_fin' => now()->startOfMonth()->addDays(24),
            'estado' => EstadoPlanificacion::Programado,
        ]);
        $grupo = Grupo::create([
            'modulo_id' => $modulo->id,
            'cohorte_id' => $cohorte->id,
            'docente_id' => $docente->id,
            'nombre' => 'Seguridad – Edición 1 – Grupo 1',
            'modalidad' => Modalidad::Virtual,
            'estado' => EstadoPlanificacion::Programado,
        ]);
        $matricula = Matricula::create(['estudiante_id' => $estudiante->id, 'estado' => EstadoMatricula::Activo, 'fecha_matricula' => now()]);
        InscripcionGrupo::create(['matricula_id' => $matricula->id, 'grupo_id' => $grupo->id]);

        $this->actingAs($estudiante)->get(route('calendario'))
            ->assertOk()
            ->assertSee('Inicia: Seguridad · Seguridad – Edición 1 – Grupo 1', false)
            ->assertSee('Termina: Seguridad · Seguridad – Edición 1 – Grupo 1', false);
    }

    public function test_el_administrador_no_entra_al_calendario(): void
    {
        $this->actingAs(User::factory()->create(['rol' => Rol::Administrador]))
            ->get(route('calendario'))->assertForbidden();
    }

    public function test_la_busqueda_de_usuarios_filtra_por_nombre(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Administrador]);
        User::factory()->create(['rol' => Rol::Estudiante, 'nombres' => 'Valeria', 'apellidos' => 'Ortiz']);
        User::factory()->create(['rol' => Rol::Estudiante, 'nombres' => 'Gonzalo', 'apellidos' => 'Pinto']);

        $this->actingAs($admin)->get(route('usuarios.index', ['q' => 'valeria']))
            ->assertOk()
            ->assertSee('Valeria')
            ->assertDontSee('Gonzalo');
    }

    public function test_la_busqueda_de_programas_no_rompe_con_inyeccion(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Administrador]);
        Programa::create(['codigo' => 'P-1', 'nombre' => 'Diplomado', 'version' => '1.0', 'estado_comercial' => EstadoComercial::Abierto]);

        $this->actingAs($admin)->get(route('programas.index', ['q' => "' OR '1'='1"]))
            ->assertOk()
            ->assertSee('No se encontraron programas');
    }
}
