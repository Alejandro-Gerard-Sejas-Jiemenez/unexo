<?php

use App\Support\RestriccionCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('modulo_id')->constrained('modulos')->restrictOnDelete();
            $table->foreignUuid('cohorte_id')->nullable()->constrained('cohortes')->restrictOnDelete();
            $table->foreignUuid('docente_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('aula_id')->nullable()->constrained('aulas')->restrictOnDelete();
            $table->string('nombre', 80);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('modalidad', 15)->default('virtual');
            $table->string('enlace_videoconferencia')->nullable();
            $table->unsignedSmallInteger('cupo_minimo')->nullable();
            $table->string('estado', 20)->default('programado');
            $table->timestamps();

            $table->index('docente_id');
        });

        RestriccionCheck::agregar('grupos', 'grupos_estado_check', "estado IN ('programado','en_curso','finalizado','cancelado')");
        RestriccionCheck::agregar('grupos', 'grupos_modalidad_check', "modalidad IN ('virtual','presencial')");
        RestriccionCheck::agregar('grupos', 'grupos_fechas_check', 'fecha_fin >= fecha_inicio');
    }

    public function down(): void
    {
        Schema::dropIfExists('grupos');
    }
};
