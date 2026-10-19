<?php

namespace Tests\Feature;

use App\Enums\Certificado;
use App\Enums\EstadoComercial;
use App\Enums\EstadoPlanificacion;
use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Models\Aula;
use App\Models\ClienteApi;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\HorarioGrupo;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class CrmCatalogoApiTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(): array
    {
        $cliente = ClienteApi::create(['nombre' => 'CRM Ventas']);

        return [$cliente, ['Authorization' => 'Bearer '.$cliente->createToken('crm')->plainTextToken]];
    }

    private function moduloAbierto(string $codigo, array $extra = []): Modulo
    {
        return Modulo::create($extra + [
            'codigo' => $codigo,
            'nombre' => "Módulo {$codigo}",
            'duracion_horas' => 30,
            'estado_comercial' => EstadoComercial::Abierto,
        ]);
    }

    private function programaAbierto(array $extra = []): Programa
    {
        $programa = Programa::create($extra + [
            'codigo' => 'P-A',
            'nombre' => 'Programa abierto',
            'version' => '1.0',
            'estado_comercial' => EstadoComercial::Abierto,
            'certificado_otorga' => Certificado::Certificado,
        ]);
        $programa->modulos()->attach($this->moduloAbierto('M-'.$programa->codigo)->id, ['orden' => 1]);

        return $programa;
    }

    public function test_catalogo_crm_exige_autenticacion(): void
    {
        $this->getJson('/api/v1/crm/catalogo')->assertUnauthorized();
        $this->getJson('/api/v1/crm/programas')->assertUnauthorized();
        $this->getJson('/api/v1/crm/modulos')->assertUnauthorized();
    }

    // CA-34, CA-50: oferta abierta sin datos retirados, con certificado como código.
    public function test_catalogo_crm_retorna_oferta_abierta(): void
    {
        [$cliente, $headers] = $this->cliente();

        $docente = User::factory()->create(['rol' => Rol::Docente, 'nombres' => 'Carlos', 'apellidos' => 'Docente']);
        $aula = Aula::create(['nombre' => 'Aula 101', 'capacidad' => 30]);

        $programa = Programa::create([
            'codigo' => 'DIP-AI',
            'nombre' => 'Diplomado en Inteligencia Artificial',
            'version' => '1.0',
            'descripcion' => 'Formación especializada en IA moderna.',
            'estado_comercial' => EstadoComercial::Abierto,
            'precio' => 4500.00,
            'certificado_otorga' => Certificado::Certificado,
        ]);

        $modulo1 = Modulo::create([
            'codigo' => 'MOD-101',
            'nombre' => 'Fundamentos de Machine Learning',
            'duracion_horas' => 40,
            'estado_comercial' => EstadoComercial::Abierto,
        ]);

        $modulo2 = Modulo::create([
            'codigo' => 'MOD-102',
            'nombre' => 'Redes Neuronales Profundas',
            'duracion_horas' => 50,
            'estado_comercial' => EstadoComercial::Abierto,
        ]);

        $programa->modulos()->attach([
            $modulo1->id => ['orden' => 1],
            $modulo2->id => ['orden' => 2],
        ]);

        $edicion = Cohorte::create([
            'programa_id' => $programa->id,
            'nombre' => 'Edición 1',
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => '2026-12-15',
            'estado' => EstadoPlanificacion::Programado,
        ]);

        // El grupo de edición no lleva fechas propias: usa las de la edición.
        Grupo::create([
            'modulo_id' => $modulo1->id,
            'cohorte_id' => $edicion->id,
            'docente_id' => $docente->id,
            'aula_id' => $aula->id,
            'nombre' => 'Grupo A',
            'modalidad' => Modalidad::Presencial,
            'estado' => EstadoPlanificacion::Programado,
        ]);

        // Programa en borrador no debe aparecer
        Programa::create([
            'codigo' => 'BORRADOR',
            'nombre' => 'Programa Oculto',
            'version' => '1.0',
            'estado_comercial' => EstadoComercial::Borrador,
        ]);

        $respuesta = $this->getJson('/api/v1/crm/catalogo', $headers)
            ->assertOk()
            ->assertJsonPath('exito', true)
            ->assertJsonPath('resumen.total_programas_abiertos', 1)
            ->assertJsonMissingPath('programas.0.nivel_academico')
            ->assertJsonMissingPath('programas.0.perfil_egreso')
            ->assertJsonMissingPath('programas.0.total_creditos')
            ->assertJsonMissingPath('programas.0.modulos.0.creditos')
            ->assertJsonMissingPath('programas.0.modulos.0.tipo_evaluacion');

        $datos = $respuesta->json();
        $this->assertCount(1, $datos['programas']);
        $this->assertEquals('DIP-AI', $datos['programas'][0]['codigo']);
        $this->assertEquals(90, $datos['programas'][0]['duracion_total_horas']);
        $this->assertSame('certificado', $datos['programas'][0]['certificado_otorga']);
        $this->assertSame('Certificado', $datos['programas'][0]['certificado_otorga_label']);
        $this->assertCount(2, $datos['programas'][0]['modulos']);
        $this->assertCount(1, $datos['programas'][0]['ediciones_disponibles']);
        $this->assertEquals('Edición 1', $datos['programas'][0]['ediciones_disponibles'][0]['nombre']);

        $grupo = $datos['programas'][0]['ediciones_disponibles'][0]['grupos'][0];
        $this->assertSame('2026-09-01', $grupo['fecha_inicio']);
        $this->assertSame('2026-12-15', $grupo['fecha_fin']);
        $this->assertSame('Carlos Docente', $grupo['docente']);
    }

    public function test_filtro_y_detalle_de_programas(): void
    {
        [$cliente, $headers] = $this->cliente();

        $prog1 = Programa::create(['codigo' => 'P1', 'nombre' => 'Big Data Analytics', 'version' => '1.0', 'estado_comercial' => EstadoComercial::Abierto]);
        $m1 = Modulo::create(['codigo' => 'M1', 'nombre' => 'Hadoop', 'estado_comercial' => EstadoComercial::Abierto]);
        $prog1->modulos()->attach($m1->id, ['orden' => 1]);

        $prog2 = Programa::create(['codigo' => 'P2', 'nombre' => 'Ciberseguridad', 'version' => '1.0', 'estado_comercial' => EstadoComercial::Abierto]);
        $m2 = Modulo::create(['codigo' => 'M2', 'nombre' => 'Hacking Ético', 'estado_comercial' => EstadoComercial::Abierto]);
        $prog2->modulos()->attach($m2->id, ['orden' => 1]);

        $this->getJson('/api/v1/crm/programas?q=Data', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.codigo', 'P1');

        $this->getJson("/api/v1/crm/programas/{$prog1->id}", $headers)
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Big Data Analytics');
    }

    // CA-35: el módulo publica su prerrequisito.
    public function test_modulos_independientes(): void
    {
        [$cliente, $headers] = $this->cliente();

        $modulo = Modulo::create([
            'codigo' => 'CURSO-PY',
            'nombre' => 'Python para Negocios',
            'duracion_horas' => 24,
            'prerrequisito' => 'Lógica básica',
            'estado_comercial' => EstadoComercial::Abierto,
        ]);

        $this->getJson('/api/v1/crm/modulos', $headers)
            ->assertOk()
            ->assertJsonPath('data.0.codigo', 'CURSO-PY')
            ->assertJsonPath('data.0.prerrequisito', 'Lógica básica')
            ->assertJsonMissingPath('data.0.creditos')
            ->assertJsonMissingPath('data.0.tipo_evaluacion');

        $this->getJson("/api/v1/crm/modulos/{$modulo->id}", $headers)
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Python para Negocios')
            ->assertJsonPath('data.prerrequisito', 'Lógica básica');
    }

    // CA-10: el precio se publica en bolivianos y sin ningún dato de descuento.
    public function test_el_precio_se_publica_en_bolivianos_sin_descuentos(): void
    {
        [, $headers] = $this->cliente();
        $programa = $this->programaAbierto(['precio' => 2500.5]);

        $respuesta = $this->getJson("/api/v1/crm/programas/{$programa->id}", $headers)
            ->assertOk()
            ->assertJsonPath('data.precio', 2500.5)
            ->assertJsonPath('data.moneda', 'BOB');

        $this->assertStringNotContainsStringIgnoringCase('descuento', $respuesta->getContent());
        $this->assertStringNotContainsStringIgnoringCase('descuento', $this->getJson('/api/v1/crm/catalogo', $headers)->getContent());
    }

    // CA-34: sin datos retirados en listado, detalle y paquete; el filtro nivel se ignora.
    public function test_programas_y_modulos_no_traen_datos_retirados_y_el_filtro_nivel_se_ignora(): void
    {
        [, $headers] = $this->cliente();
        $programa = $this->programaAbierto();

        $this->getJson('/api/v1/crm/programas?nivel=Postgrado', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissingPath('data.0.nivel_academico')
            ->assertJsonMissingPath('data.0.perfil_egreso')
            ->assertJsonMissingPath('data.0.total_creditos')
            ->assertJsonMissingPath('data.0.modulos.0.creditos')
            ->assertJsonMissingPath('data.0.modulos.0.tipo_evaluacion');

        $this->getJson("/api/v1/crm/programas/{$programa->id}", $headers)
            ->assertOk()
            ->assertJsonMissingPath('data.total_creditos')
            ->assertJsonMissingPath('data.modulos.0.creditos');

        $this->getJson('/api/v1/crm/modulos', $headers)
            ->assertOk()
            ->assertJsonMissingPath('data.0.creditos')
            ->assertJsonMissingPath('data.0.tipo_evaluacion');

        $this->getJson('/api/v1/crm/catalogo', $headers)
            ->assertOk()
            ->assertJsonMissingPath('programas.0.nivel_academico')
            ->assertJsonMissingPath('programas.0.perfil_egreso')
            ->assertJsonMissingPath('programas.0.total_creditos')
            ->assertJsonMissingPath('programas.0.modulos.0.creditos')
            ->assertJsonMissingPath('modulos_libres.0.creditos')
            ->assertJsonMissingPath('modulos_libres.0.tipo_evaluacion');
    }

    // CA-35: prerrequisito, certificado y precio llegan al CRM.
    public function test_el_crm_recibe_prerrequisito_certificado_y_precio(): void
    {
        [, $headers] = $this->cliente();
        $programa = $this->programaAbierto(['precio' => 1800, 'certificado_otorga' => Certificado::SinCertificado]);
        $programa->modulos()->first()->update(['prerrequisito' => 'Excel básico']);

        $this->getJson("/api/v1/crm/programas/{$programa->id}", $headers)
            ->assertOk()
            ->assertJsonPath('data.precio', 1800)
            ->assertJsonPath('data.certificado_otorga', 'sin_certificado')
            ->assertJsonPath('data.certificado_otorga_label', 'Sin certificado')
            ->assertJsonPath('data.modulos.0.prerrequisito', 'Excel básico');

        $this->getJson('/api/v1/crm/catalogo', $headers)
            ->assertJsonPath('programas.0.modulos.0.prerrequisito', 'Excel básico')
            ->assertJsonPath('modulos_libres.0.prerrequisito', 'Excel básico');
    }

    // CA-50: "Sin certificado" es distinto de "no definido"; un texto antiguo se lee como "no definido".
    public function test_certificado_sin_certificado_es_distinto_de_no_definido(): void
    {
        [, $headers] = $this->cliente();
        $sin = $this->programaAbierto(['codigo' => 'P-SIN', 'nombre' => 'A sin', 'certificado_otorga' => Certificado::SinCertificado]);
        $antiguo = $this->programaAbierto(['codigo' => 'P-OLD', 'nombre' => 'B antiguo']);
        $nulo = $this->programaAbierto(['codigo' => 'P-NULL', 'nombre' => 'C nulo', 'certificado_otorga' => null]);
        DB::table('programas')->where('id', $antiguo->id)->update(['certificado_otorga' => 'Certificado de Diplomado']);

        $this->getJson("/api/v1/crm/programas/{$sin->id}", $headers)
            ->assertJsonPath('data.certificado_otorga', 'sin_certificado');
        $this->getJson("/api/v1/crm/programas/{$antiguo->id}", $headers)
            ->assertJsonPath('data.certificado_otorga', 'no_definido')
            ->assertJsonPath('data.certificado_otorga_label', 'No definido');
        $this->getJson("/api/v1/crm/programas/{$nulo->id}", $headers)
            ->assertJsonPath('data.certificado_otorga', 'no_definido');
    }

    // CA-36: el grupo publica horarios, aula y solo el nombre del docente.
    public function test_el_grupo_publica_horarios_aula_y_solo_el_nombre_del_docente(): void
    {
        [, $headers] = $this->cliente();
        $docente = User::factory()->create(['rol' => Rol::Docente, 'nombres' => 'Carlos', 'apellidos' => 'Mendoza', 'email' => 'secreto@example.test']);
        $aula = Aula::create(['nombre' => 'Aula 7', 'capacidad' => 20]);
        $modulo = $this->moduloAbierto('M-H');
        $grupo = Grupo::create([
            'modulo_id' => $modulo->id,
            'docente_id' => $docente->id,
            'aula_id' => $aula->id,
            'nombre' => 'Grupo 1',
            'fecha_inicio' => '2026-10-05',
            'fecha_fin' => '2026-11-05',
            'modalidad' => Modalidad::Mixta,
            'cupo_minimo' => 12,
            'estado' => EstadoPlanificacion::Programado,
        ]);
        HorarioGrupo::create(['grupo_id' => $grupo->id, 'dia_semana' => 3, 'hora_inicio' => '19:00', 'hora_fin' => '21:00']);
        HorarioGrupo::create(['grupo_id' => $grupo->id, 'dia_semana' => 1, 'hora_inicio' => '18:30', 'hora_fin' => '20:00']);

        $respuesta = $this->getJson("/api/v1/crm/modulos/{$modulo->id}", $headers)
            ->assertOk()
            ->assertJsonPath('data.grupos_disponibles.0.modulo_id', $modulo->id)
            ->assertJsonPath('data.grupos_disponibles.0.modulo_nombre', $modulo->nombre)
            ->assertJsonPath('data.grupos_disponibles.0.docente', 'Carlos Mendoza')
            ->assertJsonPath('data.grupos_disponibles.0.aula', 'Aula 7')
            ->assertJsonPath('data.grupos_disponibles.0.fecha_inicio', '2026-10-05')
            ->assertJsonPath('data.grupos_disponibles.0.fecha_fin', '2026-11-05')
            ->assertJsonPath('data.grupos_disponibles.0.cupo_minimo', 12)
            ->assertJsonPath('data.grupos_disponibles.0.horarios.0', ['dia_semana' => 1, 'dia' => 'lunes', 'hora_inicio' => '18:30', 'hora_fin' => '20:00'])
            ->assertJsonPath('data.grupos_disponibles.0.horarios.1', ['dia_semana' => 3, 'dia' => 'miércoles', 'hora_inicio' => '19:00', 'hora_fin' => '21:00']);

        $this->assertStringNotContainsString('secreto@example.test', $respuesta->getContent());
        $this->assertStringNotContainsString('documento', $respuesta->getContent());
    }

    // CA-33: programa abierto sin módulos abiertos no aparece (listado, paquete, detalle 404); módulo abierto sin programa sí.
    public function test_programa_sin_modulos_abiertos_no_se_publica_pero_modulo_sin_programa_si(): void
    {
        [, $headers] = $this->cliente();
        $vacio = Programa::create(['codigo' => 'P-VACIO', 'nombre' => 'Sin módulos', 'version' => '1.0', 'estado_comercial' => EstadoComercial::Abierto]);
        $conCerrado = Programa::create(['codigo' => 'P-CERR', 'nombre' => 'Solo cerrados', 'version' => '1.0', 'estado_comercial' => EstadoComercial::Abierto]);
        $cerrado = Modulo::create(['codigo' => 'M-C', 'nombre' => 'Cerrado', 'estado_comercial' => EstadoComercial::Cerrado]);
        $conCerrado->modulos()->attach($cerrado->id, ['orden' => 1]);
        $suelto = $this->moduloAbierto('M-LIBRE');

        $this->getJson('/api/v1/crm/programas', $headers)->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/crm/catalogo', $headers)
            ->assertJsonPath('resumen.total_programas_abiertos', 0)
            ->assertJsonCount(0, 'programas')
            ->assertJsonPath('resumen.total_modulos_libres', 1)
            ->assertJsonPath('modulos_libres.0.id', $suelto->id);
        $this->getJson("/api/v1/crm/programas/{$vacio->id}", $headers)
            ->assertNotFound()
            ->assertJsonPath('message', 'El programa solicitado no está disponible comercialmente.');
        $this->getJson("/api/v1/crm/programas/{$conCerrado->id}", $headers)->assertNotFound();
        $this->getJson('/api/v1/crm/modulos', $headers)->assertOk()->assertJsonCount(1, 'data');
    }

    // CA-49 (parte programa y módulo): solo se publican grupos programados o en curso.
    public function test_solo_se_publican_grupos_programados_o_en_curso(): void
    {
        [, $headers] = $this->cliente();
        $programa = $this->programaAbierto();
        $modulo = $programa->modulos()->first();
        $docente = User::factory()->create(['rol' => Rol::Docente]);
        $edicion = Cohorte::create(['programa_id' => $programa->id, 'nombre' => 'Edición 1', 'fecha_inicio' => '2026-09-01', 'fecha_fin' => '2026-12-01', 'estado' => EstadoPlanificacion::EnCurso]);

        $estados = [EstadoPlanificacion::Programado, EstadoPlanificacion::EnCurso, EstadoPlanificacion::Finalizado, EstadoPlanificacion::Cancelado];
        foreach ($estados as $i => $estado) {
            Grupo::create(['modulo_id' => $modulo->id, 'cohorte_id' => $edicion->id, 'docente_id' => $docente->id, 'nombre' => "Grupo E{$i}", 'modalidad' => Modalidad::Virtual, 'estado' => $estado]);
            Grupo::create(['modulo_id' => $modulo->id, 'docente_id' => $docente->id, 'nombre' => "Grupo S{$i}", 'fecha_inicio' => '2026-10-01', 'fecha_fin' => '2026-11-01', 'modalidad' => Modalidad::Virtual, 'estado' => $estado]);
        }

        $this->getJson("/api/v1/crm/programas/{$programa->id}", $headers)
            ->assertJsonCount(2, 'data.ediciones_disponibles.0.grupos');
        $this->getJson("/api/v1/crm/modulos/{$modulo->id}", $headers)
            ->assertJsonCount(2, 'data.grupos_disponibles');
    }

    // CA-38: el detalle de un módulo cerrado o en borrador responde "no disponible".
    public function test_el_detalle_de_un_modulo_cerrado_responde_no_disponible(): void
    {
        [, $headers] = $this->cliente();
        $cerrado = Modulo::create(['codigo' => 'M-X', 'nombre' => 'Cerrado', 'estado_comercial' => EstadoComercial::Cerrado]);
        $borrador = Modulo::create(['codigo' => 'M-B', 'nombre' => 'Borrador', 'estado_comercial' => EstadoComercial::Borrador]);

        foreach ([$cerrado, $borrador] as $modulo) {
            $this->getJson("/api/v1/crm/modulos/{$modulo->id}", $headers)
                ->assertNotFound()
                ->assertJsonPath('message', 'El módulo solicitado no está disponible comercialmente.');
        }
    }

    // RF-32: un id que no es UUID responde 404.
    public function test_un_id_mal_formado_responde_404(): void
    {
        [, $headers] = $this->cliente();

        $this->getJson('/api/v1/crm/programas/no-es-uuid', $headers)->assertNotFound();
        $this->getJson('/api/v1/crm/modulos/123', $headers)->assertNotFound();
        $this->getJson('/api/v1/crm/programas/'.Str::uuid(), $headers)->assertNotFound();
    }

    // CA-39: todos los recursos niegan token inválido (401) y usuario del panel (403).
    public function test_los_recursos_niegan_token_invalido_y_usuario_del_panel(): void
    {
        $uuid = (string) Str::uuid();
        $rutas = ['catalogo', 'programas', "programas/{$uuid}", 'modulos', "modulos/{$uuid}"];

        foreach ($rutas as $ruta) {
            $this->getJson("/api/v1/crm/{$ruta}", ['Authorization' => 'Bearer token-invalido'])->assertUnauthorized();
        }

        $this->actingAs(User::factory()->create(['rol' => Rol::Administrador]), 'sanctum');
        foreach ($rutas as $ruta) {
            $this->getJson("/api/v1/crm/{$ruta}")->assertForbidden();
        }
    }
}
