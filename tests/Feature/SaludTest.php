<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\User;
use Database\Seeders\RbacMenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SaludTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['rol' => Rol::Administrador, 'estado' => 'activo']);
    }

    private function sembrar(): User
    {
        $this->seed(RbacMenuSeeder::class);

        return $this->admin();
    }

    public function test_health_responde_ok_con_la_base_sana(): void
    {
        $this->sembrar();

        $this->getJson('/health')->assertOk()->assertExactJson(['estado' => 'ok']);
    }

    public function test_health_responde_503_sin_detalles_si_falta_una_tabla(): void
    {
        $this->sembrar();
        Schema::drop('visitas_pagina');

        $this->getJson('/health')->assertStatus(503)->assertExactJson(['estado' => 'falla']);
    }

    public function test_health_detecta_la_base_vacia_aunque_las_tablas_existan(): void
    {
        $this->getJson('/health')->assertStatus(503);
    }

    public function test_health_no_cuenta_como_visita_ni_abre_sesion(): void
    {
        $this->sembrar();
        $antes = DB::table('visitas_pagina')->count();

        $this->get('/health')->assertOk();

        $this->assertSame($antes, DB::table('visitas_pagina')->count());
    }

    public function test_detalle_exige_ser_administrador(): void
    {
        $this->getJson('/api/panel/salud')->assertUnauthorized();

        $docente = User::factory()->create(['rol' => Rol::Docente, 'estado' => 'activo']);
        $this->actingAs($docente)->getJson('/api/panel/salud')->assertForbidden();
    }

    public function test_detalle_indica_que_falta_y_como_repararlo(): void
    {
        $admin = $this->sembrar();
        Schema::drop('bitacora');

        $res = $this->actingAs($admin)->getJson('/api/panel/salud')->assertOk()->assertJsonPath('estado', 'falla');

        $tablas = collect($res->json('chequeos'))->firstWhere('clave', 'tablas');
        $this->assertSame('falla', $tablas['estado']);
        $this->assertStringContainsString('bitacora', $tablas['mensaje']);
        $this->assertSame('php artisan nexo:salud --reparar', $tablas['accion']);
    }

    public function test_stripe_solo_se_exige_si_es_la_pasarela_activa(): void
    {
        $admin = $this->sembrar();

        config(['services.payments.driver' => 'simulado']);
        $pagos = collect($this->actingAs($admin)->getJson('/api/panel/salud')->json('chequeos'))->firstWhere('clave', 'pagos');
        $this->assertSame('omitido', $pagos['estado']);

        config(['services.payments.driver' => 'stripe', 'services.stripe.key' => null, 'services.stripe.secret' => 'sk_test_x', 'services.stripe.webhook_secret' => null]);
        $pagos = collect($this->actingAs($admin)->getJson('/api/panel/salud')->json('chequeos'))->firstWhere('clave', 'pagos');
        $this->assertSame('falla', $pagos['estado']);
        $this->assertStringContainsString('STRIPE_KEY', $pagos['mensaje']);
        $this->assertStringContainsString('STRIPE_WEBHOOK_SECRET', $pagos['mensaje']);
        $this->assertStringNotContainsString('sk_test_x', json_encode($pagos));
    }

    public function test_reparar_restablece_una_base_borrada(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (Schema::getTableListing() as $tabla) {
            Schema::drop($tabla);
        }

        $this->artisan('nexo:salud', ['--reparar' => true])
            ->expectsConfirmation('¿Continuar?', 'yes')
            ->assertSuccessful();

        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(User::where('rol', Rol::Administrador->value)->exists());
    }

    public function test_reparar_no_hace_nada_si_el_sistema_esta_sano(): void
    {
        $this->sembrar();

        $this->artisan('nexo:salud', ['--reparar' => true])
            ->expectsOutputToContain('No hay nada que reparar')
            ->assertSuccessful();
    }
}
