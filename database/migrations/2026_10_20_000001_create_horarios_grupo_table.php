<?php

use App\Support\RestriccionCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios_grupo', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('grupo_id')->constrained('grupos')->restrictOnDelete();
            $table->unsignedTinyInteger('dia_semana');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->timestamps();

            $table->index(['grupo_id', 'dia_semana', 'hora_inicio']);
        });

        RestriccionCheck::agregar('horarios_grupo', 'horarios_grupo_dia_check', 'dia_semana BETWEEN 1 AND 7');
        RestriccionCheck::agregar('horarios_grupo', 'horarios_grupo_horas_check', 'hora_fin > hora_inicio');
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios_grupo');
    }
};
