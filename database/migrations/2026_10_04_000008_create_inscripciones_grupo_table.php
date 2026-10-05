<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscripciones_grupo', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('matricula_id')->constrained('matriculas')->restrictOnDelete();
            $table->foreignUuid('grupo_id')->constrained('grupos')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['matricula_id', 'grupo_id']);
            $table->index('grupo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscripciones_grupo');
    }
};
