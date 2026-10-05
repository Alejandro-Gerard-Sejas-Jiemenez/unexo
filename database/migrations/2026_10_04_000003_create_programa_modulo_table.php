<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programa_modulo', function (Blueprint $table) {
            $table->foreignUuid('programa_id')->constrained('programas')->restrictOnDelete();
            $table->foreignUuid('modulo_id')->constrained('modulos')->restrictOnDelete();
            $table->unsignedSmallInteger('orden');

            $table->primary(['programa_id', 'modulo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programa_modulo');
    }
};
