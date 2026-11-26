<?php

namespace Tests\Feature;

use App\Enums\EstadoMatricula;
use App\Enums\Rol;
use App\Models\ClienteApi;
use App\Models\Cohorte;
use App\Models\Matricula;
use App\Models\MenuItem;
use App\Models\Programa;
use App\Models\Transaccion;
use App\Models\User;
use Database\Seeders\RbacMenuSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Spec 011: tablas relacionadas en la base de datos. */
class IntegridadRbacTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(): User
    {
        return User::factory()->create(['rol' => Rol::Estudiante, 'estado' => 'activo']);
    }

    private function relacion(string $tabla, string $columna): ?string
    {
        foreach (Schema::getForeignKeys($tabla) as $fk) {
            if ($fk['columns'] === [$columna]) {
                return $fk['foreign_table'].'.'.$fk['foreign_columns'][0];
            }
        }

        return null;
    }

    public function test_ca01_no_se_guarda_un_usuario_con_un_rol_inexistente(): void
    {
        $this->assertSame('roles.clave', $this->relacion('users', 'rol'));
        $usuario = $this->usuario();

        $this->expectException(QueryException::class);
        DB::table('users')->where('id', $usuario->id)->update(['rol' => 'invitado']);
    }

    public function test_ca02_el_menu_solo_exige_permisos_que_existen(): void
    {
        $this->assertSame('permisos.clave', $this->relacion('menu_items', 'permiso_clave'));

        $libre = MenuItem::create(['etiqueta' => 'DEMO libre', 'permiso_clave' => null, 'orden' => 1, 'activo' => true]);
        $this->assertDatabaseHas('menu_items', ['id' => $libre->id]);

        $this->expectException(QueryException::class);
        MenuItem::create(['etiqueta' => 'DEMO huérfana', 'permiso_clave' => 'no.existe', 'orden' => 2, 'activo' => true]);
    }

    public function test_ca03_no_se_elimina_un_rol_con_usuarios(): void
    {
        $this->usuario();

        $this->expectException(QueryException::class);
        DB::table('roles')->where('clave', 'estudiante')->delete();
    }

    public function test_ca03_no_se_elimina_un_permiso_usado_por_el_menu(): void
    {
        $this->seed(RbacMenuSeeder::class);

        $this->expectException(QueryException::class);
        DB::table('permisos')->where('clave', 'panel.ver')->delete();
    }

    public function test_ca04_una_base_nueva_permite_crear_usuarios_y_carga_el_menu(): void
    {
        $respuesta = $this->actingAs($this->usuario())->getJson('/api/panel/menu')->assertOk();

        $this->assertStringContainsString('/pagos', stripslashes($respuesta->getContent()));
        $this->assertGreaterThan(0, MenuItem::count());
    }

    public function test_ca06_las_sesiones_pertenecen_a_un_usuario_existente(): void
    {
        $this->assertSame('users.id', $this->relacion('sessions', 'user_id'));
        $usuario = $this->usuario();
        $fila = fn (string $id, ?string $userId) => ['id' => $id, 'user_id' => $userId, 'payload' => 'DEMO', 'last_activity' => time()];

        DB::table('sessions')->insert($fila('anonima', null));
        DB::table('sessions')->insert($fila('propia', $usuario->id));
        $usuario->delete();

        $this->assertSame(['anonima'], DB::table('sessions')->pluck('id')->all());

        $this->expectException(QueryException::class);
        DB::table('sessions')->insert($fila('huerfana', (string) Str::uuid()));
    }

    public function test_ca07_cada_aviso_de_stripe_queda_unido_a_su_pago(): void
    {
        $this->assertSame('transacciones.id', $this->relacion('stripe_events', 'transaccion_id'));
        config(['services.stripe.webhook_secret' => 'whsec_prueba']);

        $programa = Programa::create(['codigo' => 'DEMO-1', 'nombre' => 'DEMO-Programa', 'version' => '1.0', 'precio' => 100]);
        $cohorte = Cohorte::create(['programa_id' => $programa->id, 'nombre' => 'DEMO', 'fecha_inicio' => today(), 'fecha_fin' => today()->addMonth(), 'estado' => 'programado']);
        $matricula = Matricula::create(['estudiante_id' => $this->usuario()->id, 'cohorte_id' => $cohorte->id, 'estado' => EstadoMatricula::Pendiente, 'fecha_matricula' => today()]);
        $cargo = Transaccion::create(['matricula_id' => $matricula->id, 'monto' => 100, 'estado' => 'procesando', 'referencia' => 'DEMO-1', 'proveedor' => 'stripe', 'moneda' => 'bob', 'stripe_checkout_session_id' => 'cs_1', 'stripe_intento' => 1]);
        $sesion = ['id' => 'cs_1', 'payment_status' => 'paid', 'amount_total' => 10000, 'currency' => 'bob', 'metadata' => ['transaccion_id' => $cargo->id, 'intento' => '1']];

        $this->aviso('evt_distinto', 'checkout.session.completed', ['amount_total' => 1] + $sesion);
        $this->aviso('evt_pago', 'checkout.session.completed', $sesion);
        $this->aviso('evt_tarde', 'checkout.session.expired', ['id' => 'cs_viejo'] + $sesion);
        $this->aviso('evt_ajeno', 'checkout.session.completed', ['id' => 'cs_otro', 'payment_status' => 'paid']);

        $uniones = DB::table('stripe_events')->pluck('transaccion_id', 'stripe_event_id');
        $this->assertSame($cargo->id, $uniones['evt_distinto']);
        $this->assertSame($cargo->id, $uniones['evt_pago']);
        $this->assertSame($cargo->id, $uniones['evt_tarde']);
        $this->assertNull($uniones['evt_ajeno']);
        $this->assertSame('aprobada', $cargo->fresh()->estado);
    }

    public function test_ca08_queda_registrado_quien_creo_el_acceso_a_la_api(): void
    {
        $this->assertSame('users.id', $this->relacion('api_clientes', 'creado_por'));
        $admin = User::factory()->create(['rol' => Rol::Administrador, 'estado' => 'activo']);

        $this->actingAs($admin)->postJson('/api/panel/clientes-api', ['nombre' => 'DEMO CRM'])->assertCreated();

        $this->assertSame($admin->id, ClienteApi::sole()->creado_por);
    }

    public function test_ca09_todo_token_pertenece_a_un_acceso_existente(): void
    {
        $this->assertSame('api_clientes.id', $this->relacion('personal_access_tokens', 'tokenable_id'));
        $cliente = ClienteApi::create(['nombre' => 'DEMO CRM']);
        $cliente->createToken('crm');
        DB::table('api_clientes')->where('id', $cliente->id)->delete();
        $this->assertSame(0, DB::table('personal_access_tokens')->count());

        $this->expectException(QueryException::class);
        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => ClienteApi::class,
            'tokenable_id' => (string) Str::uuid(),
            'name' => 'DEMO huérfano',
            'token' => str_repeat('a', 64),
        ]);
    }

    public function test_ca11_el_contador_guarda_el_usuario_de_la_ultima_visita(): void
    {
        $this->assertSame('users.id', $this->relacion('visitas_pagina', 'ultimo_usuario_id'));
        $primero = $this->usuario();
        $segundo = $this->usuario();
        $visitar = fn () => $this->postJson('/api/panel/visitas', ['ruta' => '/catalogo'])->assertOk();
        $fila = fn () => DB::table('visitas_pagina')->where('ruta', '/catalogo')->sole();

        $this->actingAs($primero);
        $visitar()->assertJsonMissingPath('ultimo_usuario_id');
        $this->assertSame($primero->id, $fila()->ultimo_usuario_id);

        $this->actingAs($segundo);
        $visitar();
        $this->assertSame($segundo->id, $fila()->ultimo_usuario_id);

        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $visitar();
        $this->assertNull($fila()->ultimo_usuario_id);
        $this->assertSame(3, (int) $fila()->contador);

        $this->actingAs($primero);
        $visitar();
        $primero->delete();
        $this->assertNull($fila()->ultimo_usuario_id);
        $this->assertSame(4, (int) $fila()->contador);

        $this->expectException(QueryException::class);
        DB::table('visitas_pagina')->update(['ultimo_usuario_id' => (string) Str::uuid()]);
    }

    private function aviso(string $id, string $tipo, array $sesion): void
    {
        $cuerpo = json_encode(['id' => $id, 'type' => $tipo, 'data' => ['object' => $sesion]]);
        $t = time();

        $this->call('POST', '/api/webhooks/stripe', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$t},v1=".hash_hmac('sha256', "{$t}.{$cuerpo}", 'whsec_prueba'),
        ], $cuerpo)->assertOk();
    }
}
