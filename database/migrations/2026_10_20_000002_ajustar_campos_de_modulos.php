<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Spec 009: el módulo deja de pedir créditos y tipo de evaluación y gana un prerrequisito informativo.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Solo las que existan: la base compartida puede diferir.
        $porQuitar = array_values(array_filter(
            ['creditos', 'tipo_evaluacion'],
            fn ($c) => Schema::hasColumn('modulos', $c),
        ));

        if ($porQuitar !== []) {
            Schema::table('modulos', function (Blueprint $table) use ($porQuitar) {
                $table->dropColumn($porQuitar);
            });
        }

        if (! Schema::hasColumn('modulos', 'prerrequisito')) {
            Schema::table('modulos', function (Blueprint $table) {
                $table->string('prerrequisito', 500)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('modulos', 'prerrequisito')) {
            Schema::table('modulos', function (Blueprint $table) {
                $table->dropColumn('prerrequisito');
            });
        }

        if (! Schema::hasColumn('modulos', 'creditos')) {
            Schema::table('modulos', function (Blueprint $table) {
                $table->integer('creditos')->nullable();
            });
        }

        if (! Schema::hasColumn('modulos', 'tipo_evaluacion')) {
            Schema::table('modulos', function (Blueprint $table) {
                $table->string('tipo_evaluacion')->nullable();
            });
        }
    }
};
