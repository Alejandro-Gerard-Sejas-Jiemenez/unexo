<?php

namespace Tests\Feature;

use App\Enums\DiaSemana;
use App\Models\Grupo;
use App\Models\HorarioGrupo;
use App\Models\Modulo;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HorarioGrupoEsquemaTest extends TestCase
{
    use RefreshDatabase;

    private function crearGrupo(): Grupo
    {
        $modulo = Modulo::create(['codigo' => 'DEMO-M1', 'nombre' => 'DEMO-Modulo', 'duracion_horas' => 20]);
        $docente = User::factory()->create();

        return Grupo::create([
            'modulo_id' => $modulo->id,
            'docente_id' => $docente->id,
            'nombre' => 'Grupo 1',
            'fecha_inicio' => '2026-11-01',
            'fecha_fin' => '2026-12-01',
        ]);
    }

    public function test_la_tabla_tiene_las_columnas_esperadas(): void
    {
        $this->assertTrue(Schema::hasColumns('horarios_grupo', [
            'id', 'grupo_id', 'dia_semana', 'hora_inicio', 'hora_fin', 'created_at', 'updated_at',
        ]));
    }

    public function test_guarda_el_horario_con_dia_como_enum_y_rango_legible(): void
    {
        $grupo = $this->crearGrupo();

        $horario = HorarioGrupo::create([
            'grupo_id' => $grupo->id,
            'dia_semana' => 3,
            'hora_inicio' => '19:00:00',
            'hora_fin' => '21:00:00',
        ])->fresh();

        $this->assertSame(DiaSemana::from(3), $horario->dia_semana);
        $this->assertSame('19:00–21:00', $horario->rango);
        $this->assertTrue($horario->grupo->is($grupo));
        $this->assertTrue(Schema::hasIndex('horarios_grupo', ['grupo_id', 'dia_semana', 'hora_inicio']));
    }

    public function test_no_permite_borrar_un_grupo_con_horarios(): void
    {
        $grupo = $this->crearGrupo();
        HorarioGrupo::create([
            'grupo_id' => $grupo->id,
            'dia_semana' => 1,
            'hora_inicio' => '08:00:00',
            'hora_fin' => '10:00:00',
        ]);

        $this->expectException(QueryException::class);
        $grupo->delete();
    }
}
