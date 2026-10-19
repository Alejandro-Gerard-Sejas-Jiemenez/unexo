<?php

namespace Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Concerns\CreaDatosDeReportes;
use Tests\TestCase;

class MigracionGruposTest extends TestCase
{
    use CreaDatosDeReportes;
    use RefreshDatabase;

    private function migracion(): Migration
    {
        return require base_path('database/migrations/2026_10_20_000004_ajustar_periodo_y_numero_de_grupos.php');
    }

    /** Inserta un grupo a mano, como lo dejaría la base previa a la migración (sin numero). */
    private function insertarGrupo(string $moduloId, ?string $cohorteId, string $nombre, ?int $numero = null, string $creado = '2026-01-01 10:00:00'): string
    {
        $id = (string) Str::uuid();

        DB::table('grupos')->insert([
            'id' => $id,
            'modulo_id' => $moduloId,
            'cohorte_id' => $cohorteId,
            'docente_id' => $this->docente()->id,
            'nombre' => $nombre,
            'numero' => $numero,
            'fecha_inicio' => '2026-03-01',
            'fecha_fin' => '2026-03-30',
            'modalidad' => 'virtual',
            'estado' => 'programado',
            'created_at' => Carbon::parse($creado),
            'updated_at' => Carbon::parse($creado),
        ]);

        return $id;
    }

    private function numeroDe(string $id): mixed
    {
        return DB::table('grupos')->where('id', $id)->value('numero');
    }

    // CA-17
    public function test_los_nombres_existentes_con_formato_grupo_n_reciben_numero_y_no_cambian_de_nombre(): void
    {
        $modulo = $this->modulo();
        $cohorte = $this->cohorte();
        $uno = $this->insertarGrupo($modulo->id, $cohorte->id, 'Grupo 1');
        $doce = $this->insertarGrupo($modulo->id, $cohorte->id, 'Grupo 12');

        $this->migracion()->rellenarNumeros();

        $this->assertSame(1, (int) $this->numeroDe($uno));
        $this->assertSame(12, (int) $this->numeroDe($doce));
        $this->assertSame('Grupo 1', DB::table('grupos')->where('id', $uno)->value('nombre'));
        $this->assertSame('Grupo 12', DB::table('grupos')->where('id', $doce)->value('nombre'));
    }

    // CA-17
    public function test_los_nombres_que_no_siguen_el_formato_exacto_quedan_sin_numero_y_sin_renombrar(): void
    {
        $modulo = $this->modulo();
        $nombres = ['Grupo A', 'Grupo 01', 'Grupo 0', 'grupo 3', 'Grupo  4', 'Grupo 5 ', 'Grupo 6B', 'DEMO Grupo 7'];
        $ids = array_map(fn ($nombre) => $this->insertarGrupo($modulo->id, null, $nombre), $nombres);

        $this->migracion()->rellenarNumeros();

        foreach ($ids as $i => $id) {
            $this->assertNull($this->numeroDe($id), "«{$nombres[$i]}» no debería recibir número");
            $this->assertSame($nombres[$i], DB::table('grupos')->where('id', $id)->value('nombre'));
        }
    }

    // CA-17
    public function test_ante_nombres_duplicados_solo_el_primero_recibe_numero(): void
    {
        $modulo = $this->modulo();
        $cohorte = $this->cohorte();
        $segundo = $this->insertarGrupo($modulo->id, $cohorte->id, 'Grupo 1', null, '2026-01-02 10:00:00');
        $primero = $this->insertarGrupo($modulo->id, $cohorte->id, 'Grupo 1', null, '2026-01-01 10:00:00');

        $this->migracion()->rellenarNumeros();

        $this->assertSame(1, (int) $this->numeroDe($primero));
        $this->assertNull($this->numeroDe($segundo));
        $this->assertSame('Grupo 1', DB::table('grupos')->where('id', $segundo)->value('nombre'));
    }

    // CA-17
    public function test_el_mismo_nombre_en_contextos_distintos_recibe_numero_en_cada_uno(): void
    {
        $modulo = $this->modulo();
        $edicionA = $this->cohorte();
        $edicionB = $this->cohorte();
        $enA = $this->insertarGrupo($modulo->id, $edicionA->id, 'Grupo 1');
        $enB = $this->insertarGrupo($modulo->id, $edicionB->id, 'Grupo 1');
        $suelto = $this->insertarGrupo($modulo->id, null, 'Grupo 1');

        $this->migracion()->rellenarNumeros();

        foreach ([$enA, $enB, $suelto] as $id) {
            $this->assertSame(1, (int) $this->numeroDe($id));
        }
    }

    // CA-43
    public function test_los_grupos_de_edicion_pierden_sus_fechas_y_los_sueltos_las_conservan(): void
    {
        $modulo = $this->modulo();
        $deEdicion = $this->insertarGrupo($modulo->id, $this->cohorte()->id, 'Grupo 1');
        $suelto = $this->insertarGrupo($modulo->id, null, 'Grupo 1');

        $this->migracion()->descartarFechasDeGruposDeEdicion();

        $edicion = DB::table('grupos')->where('id', $deEdicion)->first();
        $this->assertNull($edicion->fecha_inicio);
        $this->assertNull($edicion->fecha_fin);
        $sueltoFila = DB::table('grupos')->where('id', $suelto)->first();
        $this->assertNotNull($sueltoFila->fecha_inicio);
        $this->assertNotNull($sueltoFila->fecha_fin);
    }

    public function test_las_fechas_de_un_grupo_pueden_ser_nulas(): void
    {
        $modulo = $this->modulo();
        $id = $this->insertarGrupo($modulo->id, $this->cohorte()->id, 'Grupo 1');

        DB::table('grupos')->where('id', $id)->update(['fecha_inicio' => null, 'fecha_fin' => null]);

        $this->assertNull(DB::table('grupos')->where('id', $id)->value('fecha_inicio'));
    }

    // CA-43
    public function test_el_indice_unico_rechaza_el_mismo_numero_en_la_misma_edicion(): void
    {
        $modulo = $this->modulo();
        $cohorte = $this->cohorte();
        $this->insertarGrupo($modulo->id, $cohorte->id, 'Grupo 1', 1);

        $this->expectException(QueryException::class);
        $this->insertarGrupo($modulo->id, $cohorte->id, 'Grupo 1', 1);
    }

    // CA-43
    public function test_el_indice_unico_rechaza_el_mismo_numero_entre_grupos_sueltos_del_modulo(): void
    {
        $modulo = $this->modulo();
        $this->insertarGrupo($modulo->id, null, 'Grupo 1', 1);

        $this->expectException(QueryException::class);
        $this->insertarGrupo($modulo->id, null, 'Grupo 1', 1);
    }

    // CA-43
    public function test_los_sueltos_no_chocan_con_las_ediciones_ni_entre_modulos(): void
    {
        $modulo = $this->modulo();
        $otroModulo = $this->modulo();
        $cohorte = $this->cohorte();
        $otraCohorte = $this->cohorte();

        $this->insertarGrupo($modulo->id, $cohorte->id, 'Grupo 1', 1);
        $this->insertarGrupo($modulo->id, $otraCohorte->id, 'Grupo 1', 1);
        $this->insertarGrupo($modulo->id, null, 'Grupo 1', 1);
        $this->insertarGrupo($otroModulo->id, $cohorte->id, 'Grupo 1', 1);
        $this->insertarGrupo($otroModulo->id, null, 'Grupo 1', 1);

        $this->assertSame(5, DB::table('grupos')->count());
    }

    public function test_varios_grupos_sin_numero_en_el_mismo_contexto_no_chocan(): void
    {
        $modulo = $this->modulo();
        $cohorte = $this->cohorte();
        $this->insertarGrupo($modulo->id, $cohorte->id, 'Grupo A');
        $this->insertarGrupo($modulo->id, $cohorte->id, 'Grupo B');
        $this->insertarGrupo($modulo->id, null, 'Grupo A');
        $this->insertarGrupo($modulo->id, null, 'Grupo B');

        $this->assertSame(4, DB::table('grupos')->count());
    }

    public function test_el_rollback_restaura_fechas_de_edicion_y_quita_numero(): void
    {
        $modulo = $this->modulo();
        $cohorte = $this->cohorte();
        $id = $this->insertarGrupo($modulo->id, $cohorte->id, 'Grupo 1', 1);
        DB::table('grupos')->where('id', $id)->update(['fecha_inicio' => null, 'fecha_fin' => null]);

        $this->migracion()->down();

        $fila = DB::table('grupos')->where('id', $id)->first();
        $this->assertSame('2026-03-01', substr($fila->fecha_inicio, 0, 10));
        $this->assertSame('2026-08-30', substr($fila->fecha_fin, 0, 10));
        $this->assertFalse(Schema::hasColumn('grupos', 'numero'));

        $this->migracion()->up();

        $this->assertTrue(Schema::hasColumn('grupos', 'numero'));
        $this->assertSame(1, (int) $this->numeroDe($id));
    }
}
