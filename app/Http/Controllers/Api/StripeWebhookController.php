<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Pagos\StripeCheckout;
use App\Services\PagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeCheckout $stripe, PagoService $pagos): JsonResponse
    {
        try {
            $evento = $stripe->verificarWebhook($request->getContent(), (string) $request->header('Stripe-Signature', ''));
        } catch (UnexpectedValueException) {
            return response()->json(['message' => 'No pudimos verificar el aviso.'], 400);
        }

        if (DB::table('stripe_events')->where('stripe_event_id', $evento['id'])->exists()) {
            return response()->json(['recibido' => true, 'duplicado' => true]);
        }

        // El registro del evento y su efecto van juntos: si algo falla, Stripe lo reenvía.
        DB::transaction(function () use ($evento, $pagos) {
            $sesion = $evento['data']['object'] ?? [];

            DB::table('stripe_events')->insert([
                'id' => (string) Str::uuid(),
                'stripe_event_id' => $evento['id'],
                'tipo' => $evento['type'],
                'transaccion_id' => $pagos->pagoDelAviso($sesion),
                'procesado_en' => now(),
                'created_at' => now(),
            ]);

            match ($evento['type']) {
                'checkout.session.completed', 'checkout.session.async_payment_succeeded' => $pagos->confirmarSesion($sesion),
                'checkout.session.async_payment_failed' => $pagos->liberarSesion($sesion, 'rechazada', 'El pago no se completó.'),
                'checkout.session.expired' => $pagos->liberarSesion($sesion, 'pendiente'),
                default => null,
            };
        });

        return response()->json(['recibido' => true]);
    }
}
