<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Support\RestriccionCheck;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modulos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('codigo', 30)->unique();
            $table->string('nombre');
            $table->text('requisitos_recomendados')->nullable();
            $table->unsignedSmallInteger('duracion_horas')->nullable();
            $table->boolean('es_independiente')->default(false);
            $table->string('estado_comercial', 20)->default('borrador');
            $table->timestamps();
        });

        RestriccionCheck::agregar('modulos', 'modulos_estado_check', "estado_comercial IN ('borrador','abierto','cerrado')");
    }

    public function down(): void
    {
        Schema::dropIfExists('modulos');
    }
};
