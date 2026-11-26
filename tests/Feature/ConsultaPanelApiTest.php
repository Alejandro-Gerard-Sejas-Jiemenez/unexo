<?php

namespace Tests\Feature;

use App\Enums\EstadoComercial;
use App\Enums\EstadoPlanificacion;
use App\Enums\Rol;
use App\Models\Aula;
use App\Models\ClienteApi;
use App\Models\Grupo;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lo último que quedaba en Blade: inicio, catálogo público, vistas del docente
 * y del estudiante, aulas y clientes de la API.
 */
class ConsultaPanelApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['rol' => Rol::Administrador, 'estado' => 'activo']);
    }

    private function docente(): User
    {
        return User::factory()->create(['rol' => Rol::Docente, 'estado' => 'activo']);
    }

    private function estudiante(): User
    {
        return User::factory()->create(['rol' => Rol::Estudiante, 'estado' => 'activo']);
    }

    /** Deja la petición siguiente sin sesión, para probar con token Bearer. */
    private function olvidarSesion(): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushSession();
    }

    // ------------------------------------------------------------- inicio

    public function test_el_inicio_cambia_segun_el_rol(): void
    {
        $this->actingAs($this->admin())->getJson('/api/panel/inicio')
            ->assertOk()
            ->assertJsonPath('rol', 'administrador')
            ->assertJsonStructure(['oferta', 'avisos', 'totales']);

        $this->actingAs($this->docente())->getJson('/api/panel/inicio')
            ->assertOk()
            ->assertJsonPath('rol', 'docente')
            ->assertJsonStructure(['proximos']);

        $this->actingAs($this->estudiante())->getJson('/api/panel/inicio')
            ->assertOk()
            ->assertJsonPath('rol', 'estudiante')
            ->assertJsonStructure(['proximos']);
    }

    // --------------------------------------------------- catálogo público

    /**
     * Un programa es oferta publicable solo si está abierto y tiene al menos un
     * módulo abierto: sin módulos no hay nada que cursar.
     */
    private function programaPublicable(string $codigo, string $nombre, EstadoComercial $estado): Programa
    {
        $programa = Programa::create([
            'codigo' => $codigo, 'nombre' => $nombre,
            'version' => '1.0', 'estado_comercial' => $estado,
        ]);

        $modulo = Modulo::create([
            'codigo' => $codigo.'-M', 'nombre' => $nombre.' · módulo',
            'estado_comercial' => EstadoComercial::Abierto,
        ]);
        $programa->modulos()->attach($modulo->id, ['orden' => 1]);

        return $programa;
    }

    public function test_el_catalogo_publico_solo_muestra_la_oferta_abierta(): void
    {
        $this->programaPublicable('ABIERTO', 'Programa abierto', EstadoComercial::Abierto);
        $this->programaPublicable('BORRADOR', 'Programa en borrador', EstadoComercial::Borrador);

        $respuesta = $this->actingAs($this->estudiante())
            ->getJson('/api/panel/catalogo-publico')
            ->assertOk();

        $nombres = array_column($respuesta->json('programas'), 'nombre');

        $this->assertContains('Programa abierto', $nombres);
        $this->assertNotContains('Programa en borrador', $nombres);
    }

    public function test_el_catalogo_publico_busca_por_texto(): void
    {
        $this->programaPublicable('DATA-01', 'Data Analysis', EstadoComercial::Abierto);
        $this->programaPublicable('CIBER-01', 'Ciberseguridad', EstadoComercial::Abierto);

        $respuesta = $this->actingAs($this->estudiante())
            ->getJson('/api/panel/catalogo-publico?q=Data')
            ->assertOk();

        $this->assertCount(1, $respuesta->json('programas'));
    }

    // ------------------------------------------- mis grupos y mis cursos

    public function test_el_docente_ve_solo_sus_grupos(): void
    {
        $mio = $this->docente();
        $otro = $this->docente();
        $modulo = Modulo::create(['codigo' => 'MOD-1', 'nombre' => 'Módulo 1']);

        Grupo::create([
            'modulo_id' => $modulo->id, 'docente_id' => $mio->id,
            'nombre' => 'Mi grupo', 'modalidad' => 'virtual',
            'estado' => EstadoPlanificacion::Programado,
        ]);
        Grupo::create([
            'modulo_id' => $modulo->id, 'docente_id' => $otro->id,
            'nombre' => 'Grupo ajeno', 'modalidad' => 'virtual',
            'estado' => EstadoPlanificacion::Programado,
        ]);

        $respuesta = $this->actingAs($mio)->getJson('/api/panel/mis-grupos')->assertOk();

        $this->assertCount(1, $respuesta->json('data'));
        $this->assertSame('Mi grupo', $respuesta->json('data.0.nombre'));
    }

    public function test_un_docente_no_abre_el_grupo_de_otro(): void
    {
        $mio = $this->docente();
        $otro = $this->docente();
        $modulo = Modulo::create(['codigo' => 'MOD-2', 'nombre' => 'Módulo 2']);

        $ajeno = Grupo::create([
            'modulo_id' => $modulo->id, 'docente_id' => $otro->id,
            'nombre' => 'Grupo ajeno', 'modalidad' => 'virtual',
            'estado' => EstadoPlanificacion::Programado,
        ]);

        $this->actingAs($mio)->getJson("/api/panel/mis-grupos/{$ajeno->id}")
            ->assertForbidden();
    }

    public function test_mis_grupos_es_solo_para_docentes(): void
    {
        $this->actingAs($this->estudiante())->getJson('/api/panel/mis-grupos')->assertForbidden();
        $this->actingAs($this->admin())->getJson('/api/panel/mis-cursos')->assertForbidden();
    }

    public function test_mis_cursos_lista_las_matriculas_pendientes(): void
    {
        $this->actingAs($this->estudiante())->getJson('/api/panel/mis-cursos')
            ->assertOk()
            ->assertJsonStructure(['data', 'conteo', 'total', 'hay_inscripciones', 'pendientes_de_pago']);
    }

    // --------------------------------------------------------- calendario

    public function test_el_calendario_arma_las_semanas_del_mes(): void
    {
        $respuesta = $this->actingAs($this->docente())
            ->getJson('/api/panel/calendario?mes=2026-03')
            ->assertOk()
            ->assertJsonPath('mes', '2026-03');

        // Siempre semanas completas de lunes a domingo.
        foreach ($respuesta->json('semanas') as $semana) {
            $this->assertCount(7, $semana);
        }
    }

    public function test_un_mes_invalido_cae_al_mes_actual(): void
    {
        $this->actingAs($this->docente())
            ->getJson('/api/panel/calendario?mes=no-es-un-mes')
            ->assertOk()
            ->assertJsonPath('mes', now()->format('Y-m'));
    }

    // --------------------------------------------------------------- aulas

    public function test_crear_y_editar_un_aula(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->postJson('/api/panel/aulas', [
            'nombre' => 'Aula 101', 'capacidad' => 30,
        ])->assertCreated();

        $aula = Aula::firstOrFail();

        $this->actingAs($admin)->putJson("/api/panel/aulas/{$aula->id}", [
            'nombre' => 'Aula 101 B', 'capacidad' => 25,
        ])->assertOk()->assertJsonPath('aula.nombre', 'Aula 101 B');
    }

    public function test_el_nombre_del_aula_no_se_repite(): void
    {
        Aula::create(['nombre' => 'Aula 101', 'capacidad' => 30]);

        $this->actingAs($this->admin())->postJson('/api/panel/aulas', ['nombre' => 'Aula 101'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nombre']);
    }

    // ----------------------------------------------- clientes de la API

    public function test_crear_un_cliente_devuelve_el_token_una_sola_vez(): void
    {
        $admin = $this->admin();

        $respuesta = $this->actingAs($admin)
            ->postJson('/api/panel/clientes-api', ['nombre' => 'CRM Comercial'])
            ->assertCreated();

        $token = $respuesta->json('token');
        $this->assertNotEmpty($token);

        // El listado nunca lo vuelve a mostrar.
        $listado = $this->actingAs($admin)->getJson('/api/panel/clientes-api')->assertOk();
        $this->assertStringNotContainsString($token, $listado->getContent());

        // Y el token sirve de verdad contra la API del CRM. Hay que soltar la
        // sesión del administrador: si no, el guard la resuelve a ella y el
        // middleware cliente.api rechaza con 403 antes de mirar el token.
        $this->olvidarSesion();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/crm/catalogo')
            ->assertOk();
    }

    public function test_revocar_deja_el_token_sin_efecto(): void
    {
        $admin = $this->admin();

        $creado = $this->actingAs($admin)
            ->postJson('/api/panel/clientes-api', ['nombre' => 'CRM a revocar'])
            ->assertCreated();

        $token = $creado->json('token');
        $id = $creado->json('cliente.id');

        $this->actingAs($admin)->deleteJson("/api/panel/clientes-api/{$id}")->assertOk();

        $this->olvidarSesion();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/crm/catalogo')
            ->assertUnauthorized();
    }

    public function test_solo_el_administrador_gestiona_aulas_y_clientes(): void
    {
        $docente = $this->docente();

        $this->actingAs($docente)->getJson('/api/panel/aulas')->assertForbidden();
        $this->actingAs($docente)->getJson('/api/panel/clientes-api')->assertForbidden();
    }

    public function test_el_cliente_api_queda_fuera_de_la_base_al_revocarlo(): void
    {
        $admin = $this->admin();
        $cliente = ClienteApi::create(['nombre' => 'Temporal']);

        $this->actingAs($admin)->deleteJson("/api/panel/clientes-api/{$cliente->id}")->assertOk();

        $this->assertDatabaseMissing('api_clientes', ['id' => $cliente->id]);
    }

    public function test_el_estudiante_ve_su_curso_pero_no_su_enlace_si_esta_pendiente(): void
    {
        $estudiante = $this->estudiante();
        $modulo = Modulo::create(['codigo' => 'CUR-01', 'nombre' => 'Curso Virtual']);
        $grupo = Grupo::create([
            'modulo_id' => $modulo->id,
            'docente_id' => $this->docente()->id,
            'nombre' => 'Grupo A',
            'modalidad' => 'virtual',
            'estado' => EstadoPlanificacion::EnCurso,
            'enlace_videoconferencia' => 'https://meet.example.com/privado',
            'fecha_inicio' => today()->subDay(),
            'fecha_fin' => today()->addMonth(),
        ]);

        $matricula = \App\Models\Matricula::create([
            'estudiante_id' => $estudiante->id,
            'estado' => \App\Enums\EstadoMatricula::Pendiente,
            'fecha_matricula' => today(),
        ]);
        \App\Models\InscripcionGrupo::create(['matricula_id' => $matricula->id, 'grupo_id' => $grupo->id]);

        $this->actingAs($estudiante)
            ->getJson("/api/panel/mis-cursos/{$grupo->id}")
            ->assertOk()
            ->assertJsonPath('matricula_estado', 'pendiente')
            ->assertJsonPath('grupo.enlace_videoconferencia', null);

        $matricula->update(['estado' => \App\Enums\EstadoMatricula::Activo]);

        $this->actingAs($estudiante)
            ->getJson("/api/panel/mis-cursos/{$grupo->id}")
            ->assertOk()
            ->assertJsonPath('grupo.enlace_videoconferencia', 'https://meet.example.com/privado');
    }
}
