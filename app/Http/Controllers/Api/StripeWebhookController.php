<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Pagos\PaymentGatewayInterface;
use App\Services\PagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class StripeWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        PaymentGatewayInterface $gateway,
        PagoService $pagos,
    ): JsonResponse {
        $payload = $request->getContent();
        $firma = (string) $request->header('Stripe-Signature', '');

        try {
            $evento = $gateway->verificarWebhook($payload, $firma);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }

        $yaProcesado = DB::table('stripe_events')
            ->where('stripe_event_id', $evento['id'])
            ->exists();

        if ($yaProcesado) {
            return response()->json(['recibido' => true, 'duplicado' => true]);
        }

        $objeto = $evento['data']['object'] ?? [];
        $intentId = $objeto['id'] ?? null;

        DB::transaction(function () use ($evento, $objeto, $intentId, $pagos) {
            DB::table('stripe_events')->insert([
                'id' => (string) Str::uuid(),
                'stripe_event_id' => $evento['id'],
                'tipo' => $evento['type'],
                'procesado_en' => now(),
                'created_at' => now(),
            ]);

            if (! $intentId) {
                return;
            }

            if ($evento['type'] === 'payment_intent.succeeded') {
                $pagos->confirmarPorIntent($intentId, $objeto['latest_charge'] ?? null);
            } elseif ($evento['type'] === 'payment_intent.payment_failed') {
                $pagos->rechazarPorIntent(
                    $intentId,
                    $objeto['last_payment_error']['message'] ?? 'Pago rechazado por Stripe.',
                );
            }
        });

        return response()->json(['recibido' => true]);
    }
}
