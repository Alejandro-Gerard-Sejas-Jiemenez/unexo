<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitas_pagina', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Una fila por ruta, con su contador: el total del sitio es la suma.
            $table->string('ruta', 255)->unique();
            $table->unsignedBigInteger('contador')->default(0);
            $table->timestamp('ultima_visita')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitas_pagina');
    }
};
