<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** Spec 014: asignar docentes desde la edición. */
class DocentesPorGrupoTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(Rol $rol, string $estado = 'activo'): User
    {
        return User::factory()->create(['rol' => $rol, 'estado' => $estado]);
    }

    /** @return array{Cohorte, Modulo, Modulo} */
    private function edicion(): array
    {
        $programa = Programa::create(['codigo' => 'DEMO-P1', 'nombre' => 'DEMO Programa', 'version' => '1.0']);
        $modulos = collect([1, 2])->map(fn (int $i) => Modulo::create(['codigo' => "DEMO-M{$i}", 'nombre' => "DEMO Módulo {$i}"]));
        $modulos->each(fn (Modulo $m, int $i) => $programa->modulos()->attach($m->id, ['orden' => $i + 1]));
        $cohorte = Cohorte::create(['programa_id' => $programa->id, 'nombre' => 'DEMO Edición', 'fecha_inicio' => today(), 'fecha_fin' => today()->addMonth(), 'estado' => 'programado']);

        return [$cohorte, $modulos[0], $modulos[1]];
    }

    private function abrir(Cohorte $cohorte, array $datos): TestResponse
    {
        return $this->actingAs($this->usuario(Rol::Administrador))
            ->postJson("/api/panel/cohortes/{$cohorte->id}/aperturar-grupos", $datos + ['modalidad' => 'virtual']);
    }

    private function grupo(string $estado = 'programado'): Grupo
    {
        [$cohorte, $modulo] = $this->edicion();

        return Grupo::create(['modulo_id' => $modulo->id, 'cohorte_id' => $cohorte->id, 'docente_id' => $this->usuario(Rol::Docente)->id, 'nombre' => 'Grupo 1', 'modalidad' => 'virtual', 'estado' => $estado]);
    }

    private function cambiar(Grupo $grupo, User $nuevo, ?User $como = null): TestResponse
    {
        return $this->actingAs($como ?? $this->usuario(Rol::Administrador))
            ->patchJson("/api/panel/grupos/{$grupo->id}/docente", ['docente_id' => $nuevo->id]);
    }

    public function test_ca01_cada_grupo_se_abre_con_el_docente_de_su_modulo(): void
    {
        [$cohorte, $m1, $m2] = $this->edicion();
        $uno = $this->usuario(Rol::Docente);
        $dos = $this->usuario(Rol::Docente);

        $this->abrir($cohorte, ['docentes' => [$m1->id => $uno->id, $m2->id => $dos->id]])->assertOk()->assertJsonPath('creados', 2);

        $this->assertSame($uno->id, Grupo::where('modulo_id', $m1->id)->sole()->docente_id);
        $this->assertSame($dos->id, Grupo::where('modulo_id', $m2->id)->sole()->docente_id);
    }

    public function test_ca02_si_falta_el_docente_de_un_modulo_no_se_crea_ninguno(): void
    {
        [$cohorte, $m1] = $this->edicion();
        $docente = $this->usuario(Rol::Docente);

        $this->abrir($cohorte, ['docentes' => [$m1->id => $docente->id]])
            ->assertStatus(422)
            ->assertJsonPath('errors.docentes.0', 'Elige un docente para cada módulo.');

        $this->assertSame(0, Grupo::count());
    }

    public function test_ca03_solo_se_asigna_un_docente_activo(): void
    {
        [$cohorte, $m1, $m2] = $this->edicion();
        $valido = $this->usuario(Rol::Docente);

        foreach ([$this->usuario(Rol::Estudiante), $this->usuario(Rol::Docente, 'inactivo')] as $invalido) {
            $this->abrir($cohorte, ['docentes' => [$m1->id => $valido->id, $m2->id => $invalido->id]])->assertStatus(422);
        }
        $this->assertSame(0, Grupo::count());

        $this->abrir($cohorte, ['docentes' => [$m1->id => $valido->id, $m2->id => $valido->id]])->assertOk();
        $grupo = Grupo::where('modulo_id', $m1->id)->sole();

        foreach ([$this->usuario(Rol::Estudiante), $this->usuario(Rol::Docente, 'inactivo')] as $invalido) {
            $this->cambiar($grupo, $invalido)->assertStatus(422)->assertJsonValidationErrors('docente_id');
        }
        $this->assertSame($valido->id, $grupo->fresh()->docente_id);
    }

    public function test_ca04_cambiar_el_docente_no_modifica_nada_mas(): void
    {
        $grupo = $this->grupo();
        $antes = collect($grupo->fresh()->getAttributes())->except(['docente_id', 'updated_at']);
        $nuevo = $this->usuario(Rol::Docente);

        $this->cambiar($grupo, $nuevo)->assertOk()->assertJsonPath('mensaje', 'Docente actualizado.');

        $this->assertSame($nuevo->id, $grupo->fresh()->docente_id);
        $this->assertEquals($antes, collect($grupo->fresh()->getAttributes())->except(['docente_id', 'updated_at']));
        $this->assertSame(0, $grupo->horarios()->count());
    }

    public function test_ca05_no_se_cambia_en_un_grupo_cerrado(): void
    {
        foreach (['finalizado', 'cancelado'] as $estado) {
            $grupo = $this->grupo($estado);
            $original = $grupo->docente_id;

            $this->cambiar($grupo, $this->usuario(Rol::Docente))->assertStatus(422)->assertJsonValidationErrors('docente_id');

            $this->assertSame($original, $grupo->fresh()->docente_id, $estado);
            DB::table('grupos')->delete();
            DB::table('cohortes')->delete();
            DB::table('programa_modulo')->delete();
            DB::table('programas')->delete();
            DB::table('modulos')->delete();
        }
    }

    public function test_ca06_solo_el_administrador_cambia_el_docente(): void
    {
        $grupo = $this->grupo();
        $nuevo = $this->usuario(Rol::Docente);

        foreach ([Rol::Docente, Rol::Estudiante] as $rol) {
            $this->cambiar($grupo, $nuevo, $this->usuario($rol))->assertForbidden();
        }
        $this->assertNotSame($nuevo->id, $grupo->fresh()->docente_id);
    }
}
