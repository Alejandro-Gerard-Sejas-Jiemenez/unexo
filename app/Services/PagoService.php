<?php

namespace App\Services;

use App\Enums\EstadoMatricula;
use App\Models\Matricula;
use App\Models\MetodoPago;
use App\Models\Transaccion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Módulo de pagos electrónicos: métodos de pago del usuario, pagos únicos y
 * planes de pago por cuotas.
 *
 * La pasarela es simulada, como pide el enunciado: no se contacta ningún
 * proveedor real y nunca se almacena el número completo de una tarjeta.
 */
class PagoService
{
    /** Guarda un método de pago quedándose solo con lo que no es sensible. */
    public function registrarMetodo(User $usuario, array $datos): MetodoPago
    {
        return DB::transaction(function () use ($usuario, $datos) {
            $predeterminado = (bool) ($datos['predeterminado'] ?? false)
                || ! $usuario->metodosPago()->exists();

            if ($predeterminado) {
                $usuario->metodosPago()->update(['predeterminado' => false]);
            }

            return $usuario->metodosPago()->create([
                'tipo' => $datos['tipo'],
                'titular' => $datos['titular'],
                // Del número solo sobreviven los últimos cuatro dígitos.
                'ultimos_digitos' => isset($datos['numero'])
                    ? substr(preg_replace('/\D/', '', $datos['numero']), -4)
                    : null,
                'vencimiento' => $datos['vencimiento'] ?? null,
                'etiqueta' => $datos['etiqueta'] ?? null,
                'predeterminado' => $predeterminado,
            ]);
        });
    }

    public function eliminarMetodo(MetodoPago $metodo): void
    {
        DB::transaction(function () use ($metodo) {
            $usuario = $metodo->usuario;
            $eraPredeterminado = $metodo->predeterminado;
            $metodo->delete();

            // Si se borró el predeterminado, el más reciente toma su lugar.
            if ($eraPredeterminado && $usuario) {
                $usuario->metodosPago()->latest()->limit(1)->update(['predeterminado' => true]);
            }
        });
    }

    /**
     * Procesa un pago único contra la pasarela simulada.
     * La aprobación activa la matrícula, que es el efecto que importa al negocio.
     */
    public function procesarPagoUnico(
        Matricula $matricula,
        ?MetodoPago $metodo,
        float $monto,
        User $autor,
    ): Transaccion {
        return DB::transaction(function () use ($matricula, $metodo, $monto, $autor) {
            $transaccion = Transaccion::create([
                'matricula_id' => $matricula->id,
                'metodo_pago_id' => $metodo?->id,
                'monto' => $monto,
                'estado' => 'aprobada',
                'referencia' => $this->referencia(),
                'fecha_proceso' => now(),
                'registrada_por' => $autor->id,
            ]);

            $this->activarSiEstaPagada($matricula);

            return $transaccion;
        });
    }

    /**
     * Genera el plan de cuotas de una matrícula. La última cuota absorbe el
     * redondeo, para que la suma dé exactamente el total.
     *
     * @return array<int, Transaccion>
     */
    public function generarPlanDePagos(
        Matricula $matricula,
        ?MetodoPago $metodo,
        float $total,
        int $cuotas,
        User $autor,
    ): array {
        return DB::transaction(function () use ($matricula, $metodo, $total, $cuotas, $autor) {
            $enCentavos = (int) round($total * 100);
            $base = intdiv($enCentavos, $cuotas);
            $generadas = [];

            for ($numero = 1; $numero <= $cuotas; $numero++) {
                $centavos = $numero === $cuotas
                    ? $enCentavos - $base * ($cuotas - 1)
                    : $base;

                $generadas[] = Transaccion::create([
                    'matricula_id' => $matricula->id,
                    'metodo_pago_id' => $metodo?->id,
                    'monto' => $centavos / 100,
                    'numero_cuota' => $numero,
                    'total_cuotas' => $cuotas,
                    'estado' => 'pendiente',
                    'referencia' => $this->referencia(),
                    'registrada_por' => $autor->id,
                ]);
            }

            return $generadas;
        });
    }

    public function aprobar(Transaccion $transaccion): Transaccion
    {
        return DB::transaction(function () use ($transaccion) {
            $transaccion->update(['estado' => 'aprobada', 'fecha_proceso' => now()]);
            $this->activarSiEstaPagada($transaccion->matricula);

            return $transaccion->fresh();
        });
    }

    public function rechazar(Transaccion $transaccion): Transaccion
    {
        $transaccion->update(['estado' => 'rechazada', 'fecha_proceso' => now()]);

        return $transaccion->fresh();
    }

    /** Saldo que falta pagar de una matrícula. */
    public function saldoPendiente(Matricula $matricula): float
    {
        return (float) $matricula->transacciones()->where('estado', 'pendiente')->sum('monto');
    }

    public function totalPagado(Matricula $matricula): float
    {
        return (float) $matricula->transacciones()->where('estado', 'aprobada')->sum('monto');
    }

    /**
     * Una matrícula pasa a activa cuando no le queda ninguna cuota pendiente
     * y tiene al menos un pago aprobado.
     */
    private function activarSiEstaPagada(Matricula $matricula): void
    {
        $pendientes = $matricula->transacciones()->where('estado', 'pendiente')->count();
        $aprobadas = $matricula->transacciones()->where('estado', 'aprobada')->count();

        if ($pendientes === 0 && $aprobadas > 0 && $matricula->estado !== EstadoMatricula::Retirado) {
            $matricula->update(['estado' => EstadoMatricula::Activo]);
        }
    }

    private function referencia(): string
    {
        return 'TRX-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
    }
}
