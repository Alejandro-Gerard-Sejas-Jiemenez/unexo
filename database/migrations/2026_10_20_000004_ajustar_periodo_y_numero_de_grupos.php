<?php

use App\Support\RestriccionCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Los change() van primero: en SQLite reconstruyen la tabla y borrarían los índices ya creados.
        Schema::table('grupos', fn (Blueprint $table) => $table->date('fecha_inicio')->nullable()->change());
        Schema::table('grupos', fn (Blueprint $table) => $table->date('fecha_fin')->nullable()->change());
        Schema::table('grupos', fn (Blueprint $table) => $table->unsignedSmallInteger('numero')->nullable());

        $this->rellenarNumeros();
        $this->descartarFechasDeGruposDeEdicion();

        RestriccionCheck::agregar(
            'grupos',
            'grupos_periodo_check',
            '(cohorte_id IS NULL AND fecha_inicio IS NOT NULL AND fecha_fin IS NOT NULL) OR (cohorte_id IS NOT NULL AND fecha_inicio IS NULL AND fecha_fin IS NULL)'
        );

        DB::statement('CREATE UNIQUE INDEX grupos_numero_edicion_unico ON grupos (modulo_id, cohorte_id, numero) WHERE cohorte_id IS NOT NULL AND numero IS NOT NULL');
        DB::statement('CREATE UNIQUE INDEX grupos_numero_suelto_unico ON grupos (modulo_id, numero) WHERE cohorte_id IS NULL AND numero IS NOT NULL');
    }

    /**
     * Asigna `numero` solo a los nombres con formato exacto "Grupo N" (sin ceros a la izquierda).
     * Los demás conservan nombre y quedan sin número; ante duplicados solo el primero lo recibe.
     */
    public function rellenarNumeros(): void
    {
        $tomados = [];

        $grupos = DB::table('grupos')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['id', 'modulo_id', 'cohorte_id', 'nombre']);

        foreach ($grupos as $grupo) {
            if (! preg_match('/^Grupo ([1-9][0-9]*)$/D', $grupo->nombre, $coincidencia)) {
                continue;
            }

            $numero = (int) $coincidencia[1];
            $contexto = $grupo->modulo_id.'|'.($grupo->cohorte_id ?? 'suelto').'|'.$numero;

            if (isset($tomados[$contexto]) || $numero > 65535) {
                continue;
            }

            $tomados[$contexto] = true;
            DB::table('grupos')->where('id', $grupo->id)->update(['numero' => $numero]);
        }
    }

    /** El período de un grupo de edición es el de la edición (RF-15); sus fechas propias se descartan. */
    public function descartarFechasDeGruposDeEdicion(): void
    {
        DB::table('grupos')
            ->whereNotNull('cohorte_id')
            ->update(['fecha_inicio' => null, 'fecha_fin' => null]);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS grupos_numero_edicion_unico');
        DB::statement('DROP INDEX IF EXISTS grupos_numero_suelto_unico');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE grupos DROP CONSTRAINT IF EXISTS grupos_periodo_check');
        }

        // Pérdida declarada: los tramos originales por grupo no se recuperan; quedan las fechas de la edición.
        DB::statement('UPDATE grupos SET fecha_inicio = (SELECT c.fecha_inicio FROM cohortes c WHERE c.id = grupos.cohorte_id), fecha_fin = (SELECT c.fecha_fin FROM cohortes c WHERE c.id = grupos.cohorte_id) WHERE cohorte_id IS NOT NULL AND fecha_inicio IS NULL');

        Schema::table('grupos', fn (Blueprint $table) => $table->date('fecha_inicio')->nullable(false)->change());
        Schema::table('grupos', fn (Blueprint $table) => $table->date('fecha_fin')->nullable(false)->change());
        Schema::table('grupos', fn (Blueprint $table) => $table->dropColumn('numero'));
    }
};
