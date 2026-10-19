<?php

namespace Tests\Feature;

use App\Enums\EstadoComercial;
use App\Enums\EstadoPlanificacion;
use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Models\ClienteApi;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CrmEdicionesApiTest extends TestCase
{
    use RefreshDatabase;

    private function headers(): array
    {
        $cliente = ClienteApi::firstOrCreate(['nombre' => 'CRM Ventas']);

        return ['Authorization' => 'Bearer '.$cliente->createToken('crm')->plainTextToken];
    }

    private function programaAbierto(string $codigo = 'P-A', int $modulos = 1): Programa
    {
        $programa = Programa::create([
            'codigo' => $codigo,
            'nombre' => "Programa {$codigo}",
            'version' => '1.0',
            'estado_comercial' => EstadoComercial::Abierto,
        ]);
        for ($i = 1; $i <= $modulos; $i++) {
            $modulo = Modulo::create(['codigo' => "M-{$codigo}-{$i}", 'nombre' => "Módulo {$codigo}-{$i}", 'estado_comercial' => EstadoComercial::Abierto]);
            $programa->modulos()->attach($modulo->id, ['orden' => $i]);
        }

        return $programa;
    }

    private function edicion(Programa $programa, EstadoPlanificacion $estado = EstadoPlanificacion::Programado, string $inicio = '2026-09-01'): Cohorte
    {
        return Cohorte::create([
            'programa_id' => $programa->id,
            'fecha_inicio' => $inicio,
            'fecha_fin' => '2026-12-15',
            'estado' => $estado,
        ]);
    }

    private function grupo(Modulo $modulo, ?Cohorte $edicion, string $nombre, EstadoPlanificacion $estado = EstadoPlanificacion::Programado, ?int $numero = null): Grupo
    {
        return Grupo::create([
            'modulo_id' => $modulo->id,
            'cohorte_id' => $edicion?->id,
            'docente_id' => User::factory()->create(['rol' => Rol::Docente, 'nombres' => 'Carlos', 'apellidos' => 'Mendoza', 'email' => 'secreto'.Str::random(6).'@example.test'])->id,
            'nombre' => $nombre,
            'numero' => $numero,
            'fecha_inicio' => $edicion ? null : '2026-10-01',
            'fecha_fin' => $edicion ? null : '2026-11-01',
            'modalidad' => Modalidad::Virtual,
            'estado' => $estado,
        ]);
    }

    // CA-37: la edición trae programa, nombre, fechas, estado y grupos.
    public function test_la_edicion_trae_programa_nombre_fechas_estado_y_grupos(): void
    {
        $programa = $this->programaAbierto();
        $edicion = $this->edicion($programa, EstadoPlanificacion::EnCurso);
        $this->grupo($programa->modulos()->first(), $edicion, 'Grupo 1');

        $this->getJson("/api/v1/crm/ediciones/{$edicion->id}", $this->headers())
            ->assertOk()
            ->assertJsonPath('data.id', $edicion->id)
            ->assertJsonPath('data.programa', ['id' => $programa->id, 'codigo' => 'P-A', 'nombre' => 'Programa P-A', 'version' => '1.0'])
            ->assertJsonPath('data.nombre', 'Edición 1')
            ->assertJsonPath('data.fecha_inicio', '2026-09-01')
            ->assertJsonPath('data.fecha_fin', '2026-12-15')
            ->assertJsonPath('data.estado', 'en_curso')
            ->assertJsonPath('data.estado_label', 'En curso')
            ->assertJsonPath('data.grupos_abiertos_count', 1)
            ->assertJsonPath('data.grupos.0.nombre', 'Grupo 1')
            ->assertJsonPath('data.grupos.0.fecha_inicio', '2026-09-01');

        $this->getJson('/api/v1/crm/ediciones', $this->headers())
            ->assertOk()
            ->assertJsonPath('data.0.programa.codigo', 'P-A');
    }

    // CA-37: dentro del programa la edición no repite el programa contenedor.
    public function test_la_edicion_dentro_del_programa_omite_el_programa(): void
    {
        $programa = $this->programaAbierto();
        $this->edicion($programa);

        $this->getJson("/api/v1/crm/programas/{$programa->id}", $this->headers())
            ->assertOk()
            ->assertJsonMissingPath('data.ediciones_disponibles.0.programa')
            ->assertJsonPath('data.ediciones_disponibles.0.nombre', 'Edición 1');
    }

    // CA-48: solo ediciones programadas o en curso de programas visibles.
    public function test_solo_se_publican_ediciones_programadas_o_en_curso_de_programas_visibles(): void
    {
        $programa = $this->programaAbierto();
        $programado = $this->edicion($programa, EstadoPlanificacion::Programado, '2026-09-01');
        $enCurso = $this->edicion($programa, EstadoPlanificacion::EnCurso, '2026-10-01');
        $finalizada = $this->edicion($programa, EstadoPlanificacion::Finalizado);
        $cancelada = $this->edicion($programa, EstadoPlanificacion::Cancelado);

        $borrador = Programa::create(['codigo' => 'P-B', 'nombre' => 'Borrador', 'version' => '1.0', 'estado_comercial' => EstadoComercial::Borrador]);
        $sinAbiertos = Programa::create(['codigo' => 'P-C', 'nombre' => 'Sin abiertos', 'version' => '1.0', 'estado_comercial' => EstadoComercial::Abierto]);
        $sinAbiertos->modulos()->attach(Modulo::create(['codigo' => 'M-C', 'nombre' => 'Cerrado', 'estado_comercial' => EstadoComercial::Cerrado])->id, ['orden' => 1]);
        $deBorrador = $this->edicion($borrador);
        $deSinAbiertos = $this->edicion($sinAbiertos);

        $this->getJson('/api/v1/crm/ediciones', $this->headers())
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $programado->id)
            ->assertJsonPath('data.1.id', $enCurso->id);

        foreach ([$finalizada, $cancelada, $deBorrador, $deSinAbiertos] as $oculta) {
            $this->getJson("/api/v1/crm/ediciones/{$oculta->id}", $this->headers())
                ->assertNotFound()
                ->assertJsonPath('message', 'La edición solicitada no está disponible.');
        }
        $this->getJson("/api/v1/crm/ediciones/{$programado->id}", $this->headers())->assertOk();
    }

    // CA-49: solo grupos programados o en curso y de la edición; los sueltos no aparecen; orden por módulo y número.
    public function test_solo_grupos_programados_o_en_curso_de_la_edicion_y_ordenados(): void
    {
        $programa = $this->programaAbierto('P-A', 2);
        [$m1, $m2] = $programa->modulos()->get()->all();
        $edicion = $this->edicion($programa);
        $otra = $this->edicion($programa);

        $this->grupo($m2, $edicion, 'Grupo 1', numero: 1);
        $this->grupo($m1, $edicion, 'Grupo 2', EstadoPlanificacion::EnCurso, numero: 2);
        $this->grupo($m1, $edicion, 'Grupo 1', numero: 1);
        $this->grupo($m1, $edicion, 'Finalizado', EstadoPlanificacion::Finalizado);
        $this->grupo($m1, $edicion, 'Cancelado', EstadoPlanificacion::Cancelado);
        $this->grupo($m1, null, 'Suelto');
        $this->grupo($m1, $otra, 'De otra edición');

        $respuesta = $this->getJson("/api/v1/crm/ediciones/{$edicion->id}", $this->headers())
            ->assertOk()
            ->assertJsonPath('data.grupos_abiertos_count', 3)
            ->assertJsonCount(3, 'data.grupos');

        $this->assertSame(
            [$m1->id.'Grupo 1', $m1->id.'Grupo 2', $m2->id.'Grupo 1'],
            collect($respuesta->json('data.grupos'))->map(fn ($g) => $g['modulo_id'].$g['nombre'])->all(),
        );
    }

    // CA-48: filtros por programa y estado; los valores desconocidos se ignoran.
    public function test_filtros_de_programa_y_estado_y_valores_desconocidos_se_ignoran(): void
    {
        $uno = $this->programaAbierto('P-1');
        $dos = $this->programaAbierto('P-2');
        $this->edicion($uno, EstadoPlanificacion::Programado);
        $this->edicion($uno, EstadoPlanificacion::EnCurso, '2026-10-01');
        $this->edicion($dos, EstadoPlanificacion::Programado);
        $headers = $this->headers();

        $this->getJson("/api/v1/crm/ediciones?programa_id={$uno->id}", $headers)->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/crm/ediciones?estado=en_curso', $headers)->assertJsonCount(1, 'data');
        $this->getJson("/api/v1/crm/ediciones?programa_id={$dos->id}&estado=en_curso", $headers)->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/crm/ediciones?programa_id=no-es-uuid', $headers)->assertJsonCount(3, 'data');
        $this->getJson('/api/v1/crm/ediciones?estado=finalizado', $headers)->assertJsonCount(3, 'data');
        $this->getJson('/api/v1/crm/ediciones?estado=cualquiera', $headers)->assertJsonCount(3, 'data');
    }

    // RF-32: un id que no es UUID responde 404.
    public function test_un_id_mal_formado_o_inexistente_responde_404(): void
    {
        $headers = $this->headers();

        $this->getJson('/api/v1/crm/ediciones/no-es-uuid', $headers)->assertNotFound();
        $this->getJson('/api/v1/crm/ediciones/'.Str::uuid(), $headers)
            ->assertNotFound()
            ->assertJsonPath('message', 'La edición solicitada no está disponible.');
    }

    // CA-39: token inválido (401) y usuario del panel (403) en ambas rutas.
    public function test_las_rutas_niegan_token_invalido_y_usuario_del_panel(): void
    {
        $rutas = ['ediciones', 'ediciones/'.Str::uuid()];

        foreach ($rutas as $ruta) {
            $this->getJson("/api/v1/crm/{$ruta}", ['Authorization' => 'Bearer token-invalido'])->assertUnauthorized();
        }

        $this->actingAs(User::factory()->create(['rol' => Rol::Administrador]), 'sanctum');
        foreach ($rutas as $ruta) {
            $this->getJson("/api/v1/crm/{$ruta}")->assertForbidden();
        }
    }

    // CA-37: el número de consultas no crece con la cantidad de ediciones ni de grupos.
    public function test_el_listado_no_hace_consultas_por_cada_edicion(): void
    {
        $headers = $this->headers();
        $programa = $this->programaAbierto('P-N', 2);
        $modulo = $programa->modulos()->first();
        $edicion = $this->edicion($programa);
        $this->grupo($modulo, $edicion, 'Grupo 1');

        $contar = function () use ($headers): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->getJson('/api/v1/crm/ediciones', $headers)->assertOk();

            return count(DB::getQueryLog());
        };
        $contar(); // la primera llamada incluye consultas de autenticación
        $antes = $contar();

        foreach (range(1, 3) as $i) {
            $otra = $this->edicion($this->programaAbierto("P-X{$i}"));
            $this->grupo($modulo, $otra, "Grupo {$i}");
            $this->grupo($modulo, $otra, "Grupo b{$i}");
        }

        $this->assertSame($antes, $contar());
    }

    // CA-36: del docente solo se publica el nombre.
    public function test_el_docente_se_publica_solo_con_su_nombre(): void
    {
        $programa = $this->programaAbierto();
        $edicion = $this->edicion($programa);
        $this->grupo($programa->modulos()->first(), $edicion, 'Grupo 1');

        $respuesta = $this->getJson("/api/v1/crm/ediciones/{$edicion->id}", $this->headers())
            ->assertOk()
            ->assertJsonPath('data.grupos.0.docente', 'Carlos Mendoza');

        $this->assertStringNotContainsString('@example.test', $respuesta->getContent());
        $this->assertStringNotContainsString('documento', $respuesta->getContent());
    }
}
