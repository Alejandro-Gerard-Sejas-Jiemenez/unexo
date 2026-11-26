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
use App\Services\Pagos\PaymentGatewayInterface;
use App\Services\Pagos\SimulatedPaymentGateway;
use App\Services\Pagos\StripePaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagosStripeReadyTest extends TestCase
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

    private function matriculaConPrograma(User $estudiante, float $precio = 1200): Matricula
    {
        $programa = Programa::create([
            'codigo' => 'PG-'.fake()->unique()->numerify('###'),
            'nombre' => 'Programa Pago',
            'version' => '1.0',
            'precio' => $precio,
        ]);

        $cohorte = Cohorte::create([
            'programa_id' => $programa->id,
            'nombre' => 'Edición 1',
            'fecha_inicio' => today(),
            'fecha_fin' => today()->addMonths(2),
            'estado' => 'programado',
        ]);

        return Matricula::create([
            'estudiante_id' => $estudiante->id,
            'cohorte_id' => $cohorte->id,
            'estado' => EstadoMatricula::Pendiente,
            'fecha_matricula' => today(),
        ]);
    }

    public function test_el_contenedor_resuelve_la_pasarela_segun_la_configuracion(): void
    {
        config(['services.payments.driver' => 'simulado']);
        $this->app->forgetInstance(PaymentGatewayInterface::class);
        $this->assertInstanceOf(SimulatedPaymentGateway::class, app(PaymentGatewayInterface::class));

        config(['services.payments.driver' => 'stripe']);
        $this->app->forgetInstance(PaymentGatewayInterface::class);
        $this->assertInstanceOf(StripePaymentGateway::class, app(PaymentGatewayInterface::class));
    }

    public function test_el_estudiante_consulta_sus_matriculas_y_paga_matricula_en_modo_simulado(): void
    {
        $estudiante = $this->estudiante();
        $matricula = $this->matriculaConPrograma($estudiante, 950);
        MetodoPago::create([
            'user_id' => $estudiante->id,
            'tipo' => 'tarjeta',
            'titular' => $estudiante->nombre_completo,
            'ultimos_digitos' => '4242',
            'vencimiento' => '09/29',
            'predeterminado' => true,
        ]);

        $this->actingAs($estudiante)
            ->getJson('/api/panel/mis-matriculas')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('pasarela.driver', 'simulado');

        $this->actingAs($estudiante)
            ->postJson("/api/panel/matriculas/{$matricula->id}/pagar", [])
            ->assertCreated()
            ->assertJsonPath('transaccion.estado', 'aprobada');

        $this->assertSame(EstadoMatricula::Activo, $matricula->fresh()->estado);
    }

    public function test_el_estudiante_paga_sus_cuotas_propias_y_no_las_de_otro(): void
    {
        $admin = $this->admin();
        $estudiante = $this->estudiante();
        $ajeno = $this->estudiante();
        $matricula = $this->matriculaConPrograma($estudiante, 600);
        MetodoPago::create(['user_id' => $estudiante->id, 'tipo' => 'qr', 'titular' => 'DEMO']);

        $this->actingAs($admin)->postJson("/api/panel/matriculas/{$matricula->id}/cobrar", [
            'monto' => 600,
            'cuotas' => 2,
        ])->assertCreated();

        $cuotas = Transaccion::where('matricula_id', $matricula->id)->get();
        $this->assertCount(2, $cuotas);

        $this->app['auth']->forgetGuards();
        $this->flushSession();

        $this->actingAs($ajeno)
            ->postJson("/api/panel/transacciones/{$cuotas[0]->id}/pagar", [])
            ->assertForbidden();

        $this->app['auth']->forgetGuards();
        $this->flushSession();

        $this->actingAs($estudiante)
            ->postJson("/api/panel/transacciones/{$cuotas[0]->id}/pagar", [])
            ->assertOk()
            ->assertJsonPath('transaccion.estado', 'aprobada');

        $this->assertSame(EstadoMatricula::Pendiente, $matricula->fresh()->estado);

        $this->actingAs($estudiante)
            ->postJson("/api/panel/transacciones/{$cuotas[1]->id}/pagar", [])
            ->assertOk();

        $this->assertSame(EstadoMatricula::Activo, $matricula->fresh()->estado);
    }

    public function test_el_webhook_de_stripe_verifica_firma_es_idempotente_y_confirma_matricula(): void
    {
        config([
            'services.payments.driver' => 'stripe',
            'services.stripe.webhook_secret' => 'whsec_test_123',
        ]);
        $this->app->forgetInstance(PaymentGatewayInterface::class);

        $estudiante = $this->estudiante();
        $matricula = $this->matriculaConPrograma($estudiante, 800);

        $tx = Transaccion::create([
            'matricula_id' => $matricula->id,
            'monto' => 800,
            'estado' => 'procesando',
            'referencia' => 'pi_test_999',
            'proveedor' => 'stripe',
            'stripe_checkout_session_id' => 'cs_test_999',
            'stripe_intento' => 1,
        ]);

        // Firma inválida -> 400
        $payload = json_encode([
            'id' => 'evt_test_1',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_999',
                    'payment_status' => 'paid',
                    'amount_total' => 80000,
                    'currency' => 'bob',
                ],
            ],
        ]);

        $this->postJson('/api/webhooks/stripe', json_decode($payload, true), [
            'Stripe-Signature' => 't=123,v1=invalida',
        ])->assertStatus(400);

        $t = time();
        $sig = hash_hmac('sha256', "{$t}.{$payload}", 'whsec_test_123');
        $header = "t={$t},v1={$sig}";

        $this->call('POST', '/api/webhooks/stripe', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $header,
        ], $payload)->assertOk()->assertJsonPath('recibido', true);

        $this->assertSame('aprobada', $tx->fresh()->estado);
        $this->assertSame(EstadoMatricula::Activo, $matricula->fresh()->estado);

        // Reenvío del mismo evento -> idempotente
        $this->call('POST', '/api/webhooks/stripe', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $header,
        ], $payload)->assertOk()->assertJsonPath('duplicado', true);
    }
}
