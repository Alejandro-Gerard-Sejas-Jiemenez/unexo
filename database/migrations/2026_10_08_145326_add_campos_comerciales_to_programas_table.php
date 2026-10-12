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
        Schema::table('programas', function (Blueprint $table) {
            $table->decimal('precio', 10, 2)->nullable();
            $table->string('nivel_academico')->nullable();
            $table->string('imagen_portada_url')->nullable();
            $table->text('requisitos_ingreso')->nullable();
            $table->text('perfil_egreso')->nullable();
            $table->string('certificado_otorga')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programas', function (Blueprint $table) {
            $table->dropColumn(['precio', 'nivel_academico', 'imagen_portada_url', 'requisitos_ingreso', 'perfil_egreso', 'certificado_otorga']);
        });
    }
};
