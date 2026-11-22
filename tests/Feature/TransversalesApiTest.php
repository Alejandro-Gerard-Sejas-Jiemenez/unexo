<?php

namespace Tests\Feature;

use App\Enums\EstadoMatricula;
use App\Enums\Rol;
use App\Models\Bitacora;
use App\Models\Matricula;
use App\Models\MetodoPago;
use App\Models\Programa;
use App\Models\Transaccion;
use App\Models\User;
use App\Models\VisitaPagina;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Requisitos transversales del enunciado: bitácora (4), contador de
 * visitas (7), estadísticas (8), búsqueda global (9) y pagos (10).
 */
class TransversalesApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['rol' => Rol::Administrador, 'estado' => 'activo']);
    }

    private function estudiante(): User
    {
        return User::factory()->create(['rol' => Rol::Estudiante, 'estado' => 'activo']);
    }

    private function matricula(User $estudiante): Matricula
    {
        return Matricula::create([
            'estudiante_id' => $estudiante->id,
            'estado' => EstadoMatricula::Pendiente,
            'fecha_matricula' => today(),
        ]);
    }

    // ------------------------------------------------- Requisito 4: bitácora

    public function test_un_login_exitoso_queda_en_la_bitacora_con_su_ip(): void
    {
        User::factory()->create([
            'rol' => Rol::Administrador,
            'email' => 'admin@nexo.test',
            'password' => 'Demo12345',
            'estado' => 'activo',
        ]);

        $this->postJson('/api/panel/login', ['usuario' => 'admin@nexo.test', 'password' => 'Demo12345'])
            ->assertOk();

        $registro = Bitacora::where('evento', 'login_exitoso')->firstOrFail();

        $this->assertNotNull($registro->user_id);
        $this->assertNotNull($registro->ip);
    }

    public function test_un_intento_fallido_guarda_el_correo_pero_no_la_contrasena(): void
    {
        RateLimiter::clear('nadie@nexo.test|127.0.0.1');

        $this->postJson('/api/panel/login', ['usuario' => 'nadie@nexo.test', 'password' => 'secreta'])
            ->assertStatus(422);

        $registro = Bitacora::where('evento', 'login_fallido')->firstOrFail();

        $this->assertNull($registro->user_id);
        $this->assertSame('nadie@nexo.test', $registro->identificador);
        $this->assertStringNotContainsString('secreta', json_encode($registro->toArray()));
    }

    public function test_el_acceso_a_un_recurso_queda_auditado(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->getJson('/api/panel/usuarios')->assertOk();

        $this->assertDatabaseHas('bitacora', [
            'evento' => 'acceso_recurso',
            'user_id' => $admin->id,
            'recurso' => '/api/panel/usuarios',
            'codigo_respuesta' => 200,
        ]);
    }

    public function test_solo_el_administrador_consulta_la_bitacora(): void
    {
        $this->actingAs($this->estudiante())->getJson('/api/panel/bitacora')->assertForbidden();
        $this->actingAs($this->admin())->getJson('/api/panel/bitacora')->assertOk();
    }

    public function test_un_fallo_de_auditoria_no_tumba_la_peticion(): void
    {
        // Sin la tabla, el middleware debe seguir respondiendo igual.
        $admin = $this->admin();
        VisitaPagina::query()->delete();
        \Illuminate\Support\Facades\Schema::drop('visitas_pagina');

        $this->actingAs($admin)->get('/panel')->assertOk();
    }

    // ------------------------------------------- Requisito 7: contador visitas

    public function test_el_contador_de_visitas_suma_en_cada_vista(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/panel')->assertOk();
        $this->actingAs($admin)->get('/panel')->assertOk();

        $this->assertSame(2, VisitaPagina::where('ruta', '/panel')->value('contador'));
    }

    public function test_las_descargas_no_cuentan_como_visita(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->getJson('/api/panel/usuarios')->assertOk();

        $this->assertDatabaseMissing('visitas_pagina', ['ruta' => '/api/panel/usuarios']);
    }

    public function test_la_api_expone_el_contador_para_el_pie_de_pagina(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get('/panel')->assertOk();

        $this->actingAs($admin)->getJson('/api/panel/visitas?ruta=/panel')
            ->assertOk()
            ->assertJsonPath('pagina', 1)
            ->assertJsonStructure(['total', 'pagina']);
    }

    // ------------------------------------------- Requisito 8: estadísticas

    public function test_las_estadisticas_traen_negocio_y_acceso(): void
    {
        $this->actingAs($this->admin())->getJson('/api/panel/estadisticas')
            ->assertOk()
            ->assertJsonStructure([
                'negocio' => ['tarjetas', 'matriculas_por_estado', 'grupos_por_modalidad'],
                'acceso' => ['tarjetas', 'logins_por_dia', 'recursos_mas_accedidos'],
            ]);
    }

    public function test_un_docente_no_ve_las_estadisticas(): void
    {
        $docente = User::factory()->create(['rol' => Rol::Docente, 'estado' => 'activo']);

        $this->actingAs($docente)->getJson('/api/panel/estadisticas')->assertForbidden();
    }

    // ------------------------------------------- Requisito 9: búsqueda global

    public function test_la_busqueda_encuentra_programas_por_nombre_y_codigo(): void
    {
        Programa::create(['codigo' => 'DATA-01', 'nombre' => 'Data Analysis Expert', 'version' => '1.0']);

        $respuesta = $this->actingAs($this->admin())->getJson('/api/panel/buscar?q=Data')->assertOk();

        $titulos = collect($respuesta->json('resultados'))
            ->flatMap(fn ($b) => collect($b['items'])->pluck('titulo'))
            ->all();

        $this->assertContains('Data Analysis Expert', $titulos);
    }

    public function test_la_busqueda_no_devuelve_usuarios_a_quien_no_los_administra(): void
    {
        User::factory()->create(['rol' => Rol::Docente, 'nombres' => 'Zoraida', 'estado' => 'activo']);

        $respuesta = $this->actingAs($this->estudiante())->getJson('/api/panel/buscar?q=Zoraida')->assertOk();

        $bloques = collect($respuesta->json('resultados'))->pluck('titulo')->all();

        $this->assertNotContains('Usuarios', $bloques);
    }

    public function test_la_busqueda_ignora_terminos_demasiado_cortos(): void
    {
        $this->actingAs($this->admin())->getJson('/api/panel/buscar?q=a')
            ->assertOk()
            ->assertJsonPath('resultados', [])
            ->assertJsonPath('secciones', []);
    }

    // ------------------------------------------- Requisito 10: pagos

    public function test_un_metodo_de_pago_guarda_solo_los_ultimos_cuatro_digitos(): void
    {
        $estudiante = $this->estudiante();

        $this->actingAs($estudiante)->postJson('/api/panel/metodos-pago', [
            'tipo' => 'tarjeta',
            'titular' => 'Carla Ríos',
            'numero' => '4111111111111111',
            'vencimiento' => '09/29',
        ])->assertCreated()->assertJsonPath('metodo.ultimos_digitos', '1111');

        $guardado = MetodoPago::firstOrFail();

        $this->assertSame('1111', $guardado->ultimos_digitos);
        // En ninguna columna puede quedar el número completo.
        $this->assertStringNotContainsString('4111111111111111', json_encode($guardado->toArray()));
    }

    public function test_el_primer_metodo_queda_como_predeterminado(): void
    {
        $estudiante = $this->estudiante();

        $this->actingAs($estudiante)->postJson('/api/panel/metodos-pago', [
            'tipo' => 'qr', 'titular' => 'Carla Ríos',
        ])->assertCreated()->assertJsonPath('metodo.predeterminado', true);
    }

    public function test_la_tarjeta_exige_numero_y_vencimiento_con_mensaje_en_espanol(): void
    {
        $this->actingAs($this->estudiante())->postJson('/api/panel/metodos-pago', [
            'tipo' => 'tarjeta', 'titular' => 'Carla Ríos', 'vencimiento' => '13/29',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['numero'])
            ->assertJsonPath('errors.vencimiento.0', 'La fecha de vencimiento debe tener el formato MM/AA.');
    }

    public function test_nadie_borra_el_metodo_de_pago_de_otro(): void
    {
        $duenio = $this->estudiante();
        $metodo = MetodoPago::create(['user_id' => $duenio->id, 'tipo' => 'qr', 'titular' => 'Dueño']);

        $this->actingAs($this->admin())->deleteJson("/api/panel/metodos-pago/{$metodo->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('metodos_pago', ['id' => $metodo->id]);
    }

    public function test_un_pago_unico_aprobado_activa_la_matricula(): void
    {
        $admin = $this->admin();
        $matricula = $this->matricula($this->estudiante());

        $this->actingAs($admin)->postJson("/api/panel/matriculas/{$matricula->id}/cobrar", [
            'monto' => 1500,
        ])->assertCreated()->assertJsonPath('resumen.estado_matricula', 'activo');

        $this->assertSame(EstadoMatricula::Activo, $matricula->fresh()->estado);
    }

    public function test_un_plan_de_cuotas_suma_exactamente_el_total(): void
    {
        $admin = $this->admin();
        $matricula = $this->matricula($this->estudiante());

        // 1000 entre 3 no es exacto: la última cuota absorbe el redondeo.
        $this->actingAs($admin)->postJson("/api/panel/matriculas/{$matricula->id}/cobrar", [
            'monto' => 1000, 'cuotas' => 3,
        ])->assertCreated();

        $cuotas = Transaccion::where('matricula_id', $matricula->id)->get();

        $this->assertCount(3, $cuotas);
        $this->assertSame('1000.00', number_format($cuotas->sum(fn ($c) => (float) $c->monto), 2, '.', ''));
        // Con cuotas pendientes la matrícula todavía no se activa.
        $this->assertSame(EstadoMatricula::Pendiente, $matricula->fresh()->estado);
    }

    public function test_la_matricula_se_activa_al_aprobar_la_ultima_cuota(): void
    {
        $admin = $this->admin();
        $matricula = $this->matricula($this->estudiante());

        $this->actingAs($admin)->postJson("/api/panel/matriculas/{$matricula->id}/cobrar", [
            'monto' => 600, 'cuotas' => 2,
        ])->assertCreated();

        foreach (Transaccion::where('matricula_id', $matricula->id)->get() as $cuota) {
            $this->actingAs($admin)->patchJson("/api/panel/transacciones/{$cuota->id}/aprobar")->assertOk();
        }

        $this->assertSame(EstadoMatricula::Activo, $matricula->fresh()->estado);
    }

    public function test_el_estudiante_ve_sus_pagos_pero_no_los_de_otro(): void
    {
        $mios = $this->estudiante();
        $ajena = $this->matricula($this->estudiante());
        $propia = $this->matricula($mios);

        $this->actingAs($mios)->getJson("/api/panel/matriculas/{$propia->id}/transacciones")->assertOk();
        $this->actingAs($mios)->getJson("/api/panel/matriculas/{$ajena->id}/transacciones")->assertForbidden();
    }

    public function test_el_estudiante_no_puede_cobrar(): void
    {
        $estudiante = $this->estudiante();
        $matricula = $this->matricula($estudiante);

        $this->actingAs($estudiante)->postJson("/api/panel/matriculas/{$matricula->id}/cobrar", [
            'monto' => 100,
        ])->assertForbidden();
    }

    public function test_el_monto_debe_ser_positivo(): void
    {
        $matricula = $this->matricula($this->estudiante());

        $this->actingAs($this->admin())->postJson("/api/panel/matriculas/{$matricula->id}/cobrar", [
            'monto' => 0,
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.monto.0', 'El monto debe ser mayor que cero.');
    }
}
