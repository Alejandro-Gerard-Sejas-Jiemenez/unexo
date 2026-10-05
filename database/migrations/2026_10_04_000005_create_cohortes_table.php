<?php

use App\Support\RestriccionCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cohortes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('programa_id')->constrained('programas')->restrictOnDelete();
            $table->string('nombre');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('estado', 20)->default('programado');
            $table->timestamps();
        });

        RestriccionCheck::agregar('cohortes', 'cohortes_estado_check', "estado IN ('programado','en_curso','finalizado','cancelado')");
        RestriccionCheck::agregar('cohortes', 'cohortes_fechas_check', 'fecha_fin >= fecha_inicio');
    }

    public function down(): void
    {
        Schema::dropIfExists('cohortes');
    }
};
