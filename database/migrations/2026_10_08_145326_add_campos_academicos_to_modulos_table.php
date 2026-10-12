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
            $table->text('descripcion')->nullable();
            $table->integer('creditos')->nullable();
            $table->string('tipo_evaluacion')->nullable();
            $table->string('pdf_temario_url')->nullable();
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
