<?php

namespace Tests\Feature;

use App\Enums\Certificado;
use App\Enums\EstadoPlanificacion;
use App\Models\Grupo;
use App\Models\Modulo;
use App\Models\Programa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedersDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_los_datos_demo_se_cargan_con_el_esquema_nuevo(): void
    {
        $this->seed();

        $this->assertGreaterThanOrEqual(2, Modulo::whereNotNull('prerrequisito')->count());
        $this->assertSame(
            [Certificado::Certificado, Certificado::SinCertificado],
            [Programa::where('codigo', 'DEMO-P01')->first()->certificado_otorga, Programa::where('codigo', 'DEMO-P02')->first()->certificado_otorga],
        );

        $this->assertSame(0, Grupo::whereNotNull('cohorte_id')->where(fn ($q) => $q->whereNotNull('fecha_inicio')->orWhereNotNull('fecha_fin'))->count());

        $grupos = Grupo::with('horarios')->get();
        $this->assertNotEmpty($grupos);

        foreach ($grupos as $grupo) {
            $this->assertNotNull($grupo->numero);
            if (in_array($grupo->estado, [EstadoPlanificacion::Programado, EstadoPlanificacion::EnCurso], true)) {
                $this->assertGreaterThanOrEqual(1, $grupo->horarios->count());
            }
        }

        $contextos = $grupos->map(fn (Grupo $g) => $g->modulo_id.'|'.($g->cohorte_id ?? 'suelto').'|'.$g->numero);
        $this->assertSame($contextos->count(), $contextos->unique()->count());
    }

    public function test_los_seeders_son_idempotentes(): void
    {
        $this->seed();
        $total = Grupo::count();
        $this->seed();

        $this->assertSame($total, Grupo::count());
    }
}
