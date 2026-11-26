<?php

namespace Tests\Feature;

use App\Enums\EstadoMatricula;
use App\Enums\Rol;
use App\Models\Cohorte;
use App\Models\Matricula;
use App\Models\MetodoPago;
use App\Models\Programa;
use App\Models\Transaccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** Spec 010 (Fase 1). Stripe siempre se simula: ninguna prueba sale a la red. */
class PagosFase1Test extends TestCase
{
    use RefreshDatabase;

    private const SECRETO = 'whsec_prueba';

    /** @var array<string, array<string, mixed>> sesiones que «existen» en Stripe */
    private array $sesiones = [];

    /** @var array<string, string> clave de idempotencia => id de sesión */
    private array $claves = [];

    private ?string $falla = null;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.payments.driver' => 'simulado',
            'services.stripe.secret' => 'sk_test_falsa',
            'services.stripe.webhook_secret' => self::SECRETO,
        ]);

        Http::preventStrayRequests();
        Http::fake(function ($peticion) {
            if ($this->falla === 'red') {
                throw new ConnectionException('sin red');
            }
            if ($peticion->method() === 'GET') {
                $id = basename(parse_url($peticion->url(), PHP_URL_PATH));

                return Http::response($this->sesiones[$id]);
            }

            $clave = $peticion->header('Idempotency-Key')[0] ?? '';
            if (! isset($this->claves[$clave])) {
                $id = 'cs_test_'.(count($this->sesiones) + 1);
                $datos = $peticion->data();
                $this->sesiones[$id] = [
                    'id' => $id,
                    'status' => 'open',
                    'payment_status' => 'unpaid',
                    'url' => "https://checkout.stripe.test/{$id}",
                    'amount_total' => (int) $datos['line_items'][0]['price_data']['unit_amount'],
                    'currency' => $datos['line_items'][0]['price_data']['currency'],
                    'client_reference_id' => $datos['client_reference_id'],
                    'metadata' => $datos['metadata'],
                    'payment_intent' => null,
                ];
                $this->claves[$clave] = $id;
            }

            // Stripe creó la sesión, pero la respuesta nunca llegó.
            if ($this->falla === 'respuesta') {
                throw new ConnectionException('respuesta perdida');
            }

            return Http::response($this->sesiones[$this->claves[$clave]]);
        });
    }

    private function usuario(Rol $rol = Rol::Estudiante): User
    {
        return User::factory()->create(['rol' => $rol, 'estado' => 'activo']);
    }

    private function matricula(?User $estudiante = null, ?float $precio = 950): Matricula
    {
        $programa = Programa::create([
            'codigo' => 'DEMO-'.fake()->unique()->numerify('###'),
            'nombre' => 'DEMO-Programa '.fake()->unique()->numerify('####'),
            'version' => '1.0',
            'precio' => $precio,
        ]);
        $cohorte = Cohorte::create([
            'programa_id' => $programa->id,
            'nombre' => 'DEMO-Edición',
            'fecha_inicio' => today(),
            'fecha_fin' => today()->addMonths(2),
            'estado' => 'programado',
        ]);

        return Matricula::create([
            'estudiante_id' => ($estudiante ?? $this->usuario())->id,
            'cohorte_id' => $cohorte->id,
            'estado' => EstadoMatricula::Pendiente,
            'fecha_matricula' => today(),
        ]);
    }

    private function metodo(User $usuario): MetodoPago
    {
        return MetodoPago::create(['user_id' => $usuario->id, 'tipo' => 'qr', 'titular' => 'DEMO', 'predeterminado' => true]);
    }

    private function modoStripe(): void
    {
        config(['services.payments.driver' => 'stripe']);
    }

    private function pagar(Matricula $matricula, array $datos = [], ?User $como = null): TestResponse
    {
        return $this->actingAs($como ?? $matricula->estudiante)
            ->postJson("/api/panel/matriculas/{$matricula->id}/pagar", $datos);
    }

    private function pagarCargo(Transaccion $cargo, ?User $como = null): TestResponse
    {
        return $this->actingAs($como ?? $cargo->matricula->estudiante)
            ->postJson("/api/panel/transacciones/{$cargo->id}/pagar", []);
    }

    private function cobrar(Matricula $matricula, float $monto, ?int $cuotas = null): TestResponse
    {
        return $this->actingAs($this->usuario(Rol::Administrador))
            ->postJson("/api/panel/matriculas/{$matricula->id}/cobrar", array_filter(['monto' => $monto, 'cuotas' => $cuotas]));
    }

    private function resolver(Transaccion $cargo, string $accion): TestResponse
    {
        return $this->actingAs($this->usuario(Rol::Administrador))
            ->patchJson("/api/panel/transacciones/{$cargo->id}/{$accion}");
    }

    private function aviso(string $tipo, array $sesion, ?string $evento = null, string $secreto = self::SECRETO): TestResponse
    {
        $cuerpo = json_encode(['id' => $evento ?? 'evt_'.uniqid(), 'type' => $tipo, 'data' => ['object' => $sesion]]);
        $t = time();

        return $this->call('POST', '/api/webhooks/stripe', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => "t={$t},v1=".hash_hmac('sha256', "{$t}.{$cuerpo}", $secreto),
        ], $cuerpo);
    }

    private function pagada(string $id, array $cambios = []): array
    {
        return array_merge($this->sesiones[$id], ['status' => 'complete', 'payment_status' => 'paid', 'payment_intent' => "pi_{$id}"], $cambios);
    }

    public function test_ca01_el_importe_lo_fija_el_sistema(): void
    {
        $matricula = $this->matricula();
        $this->metodo($matricula->estudiante);
        $this->pagar($matricula, ['monto' => 0.01])->assertCreated();
        $this->assertSame('950.00', Transaccion::sole()->monto);

        $plan = $this->matricula();
        $this->metodo($plan->estudiante);
        $this->cobrar($plan, 950, 2)->assertCreated();
        $cuota = $plan->transacciones()->orderBy('numero_cuota')->first();
        $this->actingAs($plan->estudiante)->postJson("/api/panel/transacciones/{$cuota->id}/pagar", ['monto' => 0.01])->assertOk();
        $this->assertSame('475.00', $cuota->fresh()->monto);

        $sinPrecio = $this->matricula(precio: null);
        $this->metodo($sinPrecio->estudiante);
        $this->pagar($sinPrecio, ['monto' => 5])->assertStatus(422);
        $this->assertSame(0, $sinPrecio->transacciones()->count());
    }

    public function test_ca02_un_solo_pago_completo_o_un_solo_plan(): void
    {
        $unico = $this->matricula();
        $this->cobrar($unico, 950)->assertCreated();
        $this->cobrar($unico, 950)->assertStatus(422);
        $this->cobrar($unico, 950, 2)->assertStatus(422);
        $this->assertSame(1, $unico->transacciones()->count());

        $plan = $this->matricula();
        $this->metodo($plan->estudiante);
        $this->cobrar($plan, 950, 2)->assertCreated();
        $this->cobrar($plan, 950, 2)->assertStatus(422);
        $this->cobrar($plan, 950)->assertStatus(422);
        $this->pagar($plan)->assertStatus(422);
        $this->assertSame(2, $plan->transacciones()->count());

        $cuota = $plan->transacciones()->first();
        $this->pagarCargo($cuota)->assertOk();
        $this->pagarCargo($cuota)->assertStatus(422);
    }

    public function test_ca03_solo_activa_con_todo_pagado(): void
    {
        foreach (['pendiente', 'procesando', 'requiere_accion', 'rechazada', 'cancelada'] as $otro) {
            $matricula = $this->matricula();
            $this->metodo($matricula->estudiante);
            $this->cobrar($matricula, 950, 2)->assertCreated();
            [$a, $b] = $matricula->transacciones()->orderBy('numero_cuota')->get()->all();
            $b->update(['estado' => $otro]);

            $this->pagarCargo($a)->assertOk();

            $this->assertSame(EstadoMatricula::Pendiente, $matricula->fresh()->estado, "con la otra cuota {$otro}");
        }

        $completa = $this->matricula();
        $this->metodo($completa->estudiante);
        $this->cobrar($completa, 950, 2)->assertCreated();
        foreach ($completa->transacciones()->get() as $cuota) {
            $this->pagarCargo($cuota)->assertOk();
        }
        $this->assertSame(EstadoMatricula::Activo, $completa->fresh()->estado);

        $retirada = $this->matricula();
        $this->metodo($retirada->estudiante);
        $retirada->update(['estado' => EstadoMatricula::Retirado]);
        $this->pagar($retirada)->assertStatus(422);
        $this->assertSame(EstadoMatricula::Retirado, $retirada->fresh()->estado);
    }

    public function test_ca04_en_stripe_paga_el_duenio_en_stripe_y_sin_tarjetas(): void
    {
        $matricula = $this->matricula();
        $tarjeta = MetodoPago::create(['user_id' => $matricula->estudiante_id, 'tipo' => 'tarjeta', 'titular' => 'DEMO', 'ultimos_digitos' => '4242']);
        $this->modoStripe();

        $this->pagar($matricula)->assertCreated()
            ->assertJsonPath('url', 'https://checkout.stripe.test/cs_test_1')
            ->assertJsonPath('transaccion.estado', 'procesando');
        $this->pagar($matricula, [], $this->usuario(Rol::Administrador))->assertForbidden();
        $this->pagarCargo(Transaccion::sole(), $this->usuario(Rol::Administrador))->assertForbidden();

        $this->actingAs($matricula->estudiante)->postJson('/api/panel/metodos-pago', [
            'tipo' => 'tarjeta', 'titular' => 'DEMO', 'numero' => '4242424242424242', 'vencimiento' => '09/29',
        ])->assertStatus(422)->assertJsonValidationErrors('tipo');
        $this->actingAs($matricula->estudiante)->getJson('/api/panel/metodos-pago')
            ->assertOk()->assertJsonMissing(['id' => $tarjeta->id]);
        $this->assertSame(1, MetodoPago::count());

        Http::assertSent(fn ($p) => $p->hasHeader('Stripe-Version') && $p->hasHeader('Idempotency-Key')
            && ! array_key_exists('payment_method_types', $p->data()));
    }

    public function test_ca05_no_crea_un_segundo_intento_mientras_el_anterior_pueda_cobrar(): void
    {
        $matricula = $this->matricula();
        $this->modoStripe();

        $this->pagar($matricula)->assertCreated();
        $this->pagar($matricula)->assertCreated()->assertJsonPath('url', 'https://checkout.stripe.test/cs_test_1');
        $this->aviso('payment_intent.payment_failed', ['id' => 'pi_x'])->assertOk();
        $this->pagar($matricula)->assertCreated();
        $this->assertCount(1, $this->sesiones);

        $this->falla = 'red';
        $this->pagar($matricula)->assertStatus(422)->assertJsonPath('errors.pago.0', 'No pudimos conectar con la pasarela de pago. Inténtalo de nuevo.');
        $this->assertSame('procesando', Transaccion::sole()->estado);

        $this->falla = null;
        $this->sesiones['cs_test_1']['status'] = 'expired';
        $this->pagar($matricula)->assertCreated()->assertJsonPath('url', 'https://checkout.stripe.test/cs_test_2');
        $this->assertCount(2, $this->sesiones);
    }

    public function test_ca05_recupera_una_respuesta_perdida_y_un_cobro_sin_aviso(): void
    {
        $matricula = $this->matricula();
        $this->modoStripe();

        $this->falla = 'respuesta';
        $this->pagar($matricula)->assertStatus(422);
        $this->assertSame('procesando', Transaccion::sole()->estado);

        $this->falla = null;
        $this->pagar($matricula)->assertCreated()->assertJsonPath('url', 'https://checkout.stripe.test/cs_test_1');
        $this->assertCount(1, $this->sesiones);

        $this->sesiones['cs_test_1'] = $this->pagada('cs_test_1');
        $this->pagar($matricula)->assertCreated()->assertJsonPath('transaccion.estado', 'aprobada');
        $this->assertSame(EstadoMatricula::Activo, $matricula->fresh()->estado);
        $this->assertCount(1, $this->sesiones);
    }

    public function test_ca06_solo_una_confirmacion_autentica_y_coincidente_aprueba(): void
    {
        $matricula = $this->matricula();
        $this->modoStripe();
        $this->pagar($matricula)->assertCreated();

        $this->actingAs($matricula->estudiante)->getJson('/api/panel/mis-matriculas')->assertOk();
        $this->aviso('checkout.session.completed', $this->pagada('cs_test_1'), secreto: 'whsec_falso')->assertStatus(400);
        $this->aviso('checkout.session.completed', $this->pagada('cs_test_1', ['payment_status' => 'unpaid']))->assertOk();
        $this->aviso('checkout.session.completed', $this->pagada('cs_test_1', ['amount_total' => 1]))->assertOk();
        $this->aviso('checkout.session.completed', $this->pagada('cs_test_1', ['currency' => 'usd']))->assertOk();
        $this->aviso('checkout.session.completed', $this->pagada('cs_test_1', ['id' => 'cs_otra']))->assertOk();
        $this->assertSame('procesando', Transaccion::sole()->estado);

        $this->aviso('checkout.session.completed', $this->pagada('cs_test_1'))->assertOk();

        $this->assertSame('aprobada', Transaccion::sole()->estado);
        $this->assertSame(EstadoMatricula::Activo, $matricula->fresh()->estado);
    }

    public function test_ca07_repetidos_atrasados_y_errores_del_sistema(): void
    {
        $matricula = $this->matricula();
        $this->modoStripe();
        $this->pagar($matricula)->assertCreated();
        $sesion = $this->pagada('cs_test_1');

        // Un error al aplicar no deja el aviso como procesado.
        DB::statement("CREATE TRIGGER falla_pago BEFORE UPDATE ON transacciones BEGIN SELECT RAISE(ABORT, 'falla'); END");
        $this->aviso('checkout.session.completed', $sesion, 'evt_1')->assertStatus(500);
        $this->assertSame(0, DB::table('stripe_events')->count());
        DB::statement('DROP TRIGGER falla_pago');

        $this->travel(3)->days();
        $this->aviso('checkout.session.async_payment_succeeded', $sesion, 'evt_1')->assertOk();
        $this->assertSame('aprobada', Transaccion::sole()->estado);
        $fecha = Transaccion::sole()->fecha_proceso;

        $this->travel(1)->hours();
        $this->aviso('checkout.session.async_payment_succeeded', $sesion, 'evt_1')->assertOk()->assertJsonPath('duplicado', true);
        $this->aviso('checkout.session.completed', $sesion, 'evt_2')->assertOk();
        $this->aviso('checkout.session.async_payment_failed', $sesion, 'evt_3')->assertOk();
        $this->aviso('checkout.session.expired', $sesion, 'evt_4')->assertOk();

        $this->assertSame('aprobada', Transaccion::sole()->estado);
        $this->assertEquals($fecha, Transaccion::sole()->fecha_proceso);
    }

    public function test_ca07_una_confirmacion_de_matricula_retirada_no_cambia_nada(): void
    {
        $matricula = $this->matricula();
        $this->modoStripe();
        $this->pagar($matricula)->assertCreated();
        $matricula->update(['estado' => EstadoMatricula::Retirado]);

        $this->aviso('checkout.session.completed', $this->pagada('cs_test_1'))->assertOk();

        $this->assertSame('procesando', Transaccion::sole()->estado);
        $this->assertSame(EstadoMatricula::Retirado, $matricula->fresh()->estado);
    }

    public function test_ca07_el_fallo_diferido_deja_el_cargo_por_pagar(): void
    {
        $matricula = $this->matricula();
        $this->modoStripe();
        $this->pagar($matricula)->assertCreated();

        $this->aviso('checkout.session.async_payment_failed', $this->sesiones['cs_test_1'])->assertOk();

        $this->assertSame('rechazada', Transaccion::sole()->estado);
        $this->pagar($matricula)->assertCreated()->assertJsonPath('url', 'https://checkout.stripe.test/cs_test_2');
    }

    public function test_ca08_la_resolucion_manual_solo_sin_stripe_y_por_pagar(): void
    {
        foreach (['simulado', 'stripe'] as $modo) {
            config(['services.payments.driver' => $modo]);

            $matricula = $this->matricula();
            $this->cobrar($matricula, 950)->assertCreated();
            $cargo = $matricula->transacciones()->sole();
            $this->resolver($cargo, 'rechazar')->assertOk();
            $this->assertSame('rechazada', $cargo->fresh()->estado);
            $this->resolver($cargo, 'aprobar')->assertOk();
            $this->resolver($cargo, 'aprobar')->assertStatus(422);
            $this->resolver($cargo, 'rechazar')->assertStatus(422);

            $retirada = $this->matricula();
            $this->cobrar($retirada, 950)->assertCreated();
            $retirada->update(['estado' => EstadoMatricula::Retirado]);
            $this->resolver($retirada->transacciones()->sole(), 'aprobar')->assertStatus(422);
            $this->resolver($retirada->transacciones()->sole(), 'rechazar')->assertStatus(422);
        }

        $conStripe = $this->matricula();
        $this->pagar($conStripe)->assertCreated();
        $this->aviso('checkout.session.expired', $this->sesiones['cs_test_1'])->assertOk();
        $cargo = $conStripe->transacciones()->sole();
        $this->assertSame('pendiente', $cargo->estado);
        $this->resolver($cargo, 'aprobar')->assertStatus(422);
        $this->resolver($cargo, 'rechazar')->assertStatus(422);
    }

    public function test_ca09_el_modo_simulado_exige_metodo_y_no_toca_cargos_de_stripe(): void
    {
        $matricula = $this->matricula();
        $this->pagar($matricula)->assertStatus(422)
            ->assertJsonPath('errors.metodo_pago_id.0', 'Registra un método de pago antes de pagar.');
        $this->assertSame(0, Transaccion::count());

        $this->cobrar($matricula, 950)->assertCreated()->assertJsonPath('resumen.estado_matricula', 'pendiente');
        $this->assertSame('pendiente', Transaccion::sole()->estado);
        $this->metodo($matricula->estudiante);
        $this->pagarCargo(Transaccion::sole(), $this->usuario(Rol::Administrador))->assertOk();
        $this->assertSame('aprobada', Transaccion::sole()->estado);
        Http::assertNothingSent();

        $mixta = $this->matricula();
        $this->metodo($mixta->estudiante);
        $this->modoStripe();
        $this->pagar($mixta)->assertCreated();
        $cargo = $mixta->transacciones()->sole();
        config(['services.payments.driver' => 'simulado']);
        $this->pagar($mixta)->assertStatus(422);
        $this->pagarCargo($cargo)->assertStatus(422);
        $this->aviso('checkout.session.completed', $this->pagada('cs_test_1'), secreto: 'cualquiera')->assertStatus(400);
        config(['services.stripe.webhook_secret' => '']);
        $this->aviso('checkout.session.completed', $this->pagada('cs_test_1'), secreto: '')->assertStatus(400);
        $this->assertSame('procesando', $cargo->fresh()->estado);

        $this->modoStripe();
        $nueva = $this->matricula();
        $this->cobrar($nueva, 950)->assertCreated();
        $this->assertSame('pendiente', $nueva->transacciones()->sole()->estado);
    }

    public function test_ca10_cada_estudiante_solo_lo_suyo_y_nada_se_elimina(): void
    {
        $matricula = $this->matricula();
        $this->modoStripe();
        $this->pagar($matricula)->assertCreated();
        $ajeno = $this->usuario();

        $this->actingAs($ajeno)->getJson("/api/panel/matriculas/{$matricula->id}/transacciones")->assertForbidden();
        $this->pagar($matricula, [], $ajeno)->assertForbidden();
        $this->pagarCargo(Transaccion::sole(), $ajeno)->assertForbidden();

        $id = Transaccion::sole()->id;
        $this->aviso('checkout.session.expired', $this->sesiones['cs_test_1'])->assertOk();
        $this->assertSame($id, Transaccion::sole()->id);
    }
}
