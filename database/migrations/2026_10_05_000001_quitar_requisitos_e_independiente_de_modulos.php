<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Decisión del equipo (05/10/2026): los módulos son totalmente libres.
 * Cualquier módulo se puede tomar por separado esté o no en un programa, y no hay requisitos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modulos', function (Blueprint $table) {
            // Solo las que existan: la base compartida puede tener ya alguna eliminada.
            $table->dropColumn(array_values(array_filter(
                ['requisitos_recomendados', 'es_independiente'],
                fn ($c) => Schema::hasColumn('modulos', $c),
            )));
        });
    }

    public function down(): void
    {
        Schema::table('modulos', function (Blueprint $table) {
            $table->text('requisitos_recomendados')->nullable();
            $table->boolean('es_independiente')->default(false);
        });
    }
};
