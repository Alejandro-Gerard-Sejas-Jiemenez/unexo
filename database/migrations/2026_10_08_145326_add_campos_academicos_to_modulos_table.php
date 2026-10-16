<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('modulos', function (Blueprint $table) {
            // Cada columna solo si falta: en la base compartida `descripcion` ya existe.
            if (! Schema::hasColumn('modulos', 'descripcion')) $table->text('descripcion')->nullable();
            if (! Schema::hasColumn('modulos', 'creditos')) $table->integer('creditos')->nullable();
            if (! Schema::hasColumn('modulos', 'tipo_evaluacion')) $table->string('tipo_evaluacion')->nullable();
            if (! Schema::hasColumn('modulos', 'pdf_temario_url')) $table->string('pdf_temario_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modulos', function (Blueprint $table) {
            $table->dropColumn(['descripcion', 'creditos', 'tipo_evaluacion', 'pdf_temario_url']);
        });
    }
};
