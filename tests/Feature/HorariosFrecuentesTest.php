<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Grupo;
use App\Models\Modulo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Spec 015: bloques de horario ya usados, para reutilizarlos. */
class HorariosFrecuentesTest extends TestCase
{
    use RefreshDatabase;

    /** @param list<array{int, string, string}> $horarios */
    private function grupo(array $horarios): void
    {
        static $n = 0;
        $n++;
        $grupo = Grupo::create([
            'modulo_id' => Modulo::create(['codigo' => "DEMO-H{$n}", 'nombre' => "DEMO Módulo {$n}"])->id,
            'docente_id' => User::factory()->create(['rol' => Rol::Docente, 'estado' => 'activo'])->id,
            'nombre' => 'Grupo 1', 'modalidad' => 'virtual', 'estado' => 'programado',
        ]);

        foreach ($horarios as [$dia, $inicio, $fin]) {
            $grupo->horarios()->create(['dia_semana' => $dia, 'hora_inicio' => $inicio, 'hora_fin' => $fin]);
        }
    }

    private function ofrecidos(): array
    {
        $admin = User::factory()->create(['rol' => Rol::Administrador, 'estado' => 'activo']);

        return $this->actingAs($admin)->getJson('/api/panel/planificacion/opciones')->assertOk()->json('horarios_frecuentes');
    }

    public function test_ca03_ofrece_los_bloques_mas_usados_primero(): void
    {
        $this->grupo([[6, '09:00', '12:00']]);
        $this->grupo([[1, '19:00', '21:00'], [3, '19:00', '21:00'], [5, '19:00', '21:00']]);
        $this->grupo([[5, '19:00', '21:00'], [1, '19:00', '21:00'], [3, '19:00', '21:00']]);

        $this->assertSame([
            ['dias' => [1, 3, 5], 'hora_inicio' => '19:00', 'hora_fin' => '21:00', 'usos' => 2],
            ['dias' => [6], 'hora_inicio' => '09:00', 'hora_fin' => '12:00', 'usos' => 1],
        ], $this->ofrecidos());
    }

    public function test_ca03_horas_distintas_del_mismo_grupo_son_bloques_distintos(): void
    {
        $this->grupo([[1, '19:00', '21:00'], [6, '09:00', '12:00']]);

        $this->assertCount(2, $this->ofrecidos());
    }

    public function test_ca04_sin_horarios_no_ofrece_ninguno_y_nunca_mas_de_seis(): void
    {
        $this->assertSame([], $this->ofrecidos());

        foreach (range(8, 15) as $hora) {
            $this->grupo([[1, sprintf('%02d:00', $hora), sprintf('%02d:00', $hora + 1)]]);
        }

        $this->assertCount(6, $this->ofrecidos());
    }
}
