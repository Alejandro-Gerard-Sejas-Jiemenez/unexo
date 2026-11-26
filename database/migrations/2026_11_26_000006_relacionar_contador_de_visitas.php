<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Usuario de la última visita: nulo si fue anónima o si el contador es anterior a esta columna.
        Schema::table('visitas_pagina', function (Blueprint $table) {
            $table->foreignUuid('ultimo_usuario_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('visitas_pagina', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ultimo_usuario_id');
        });
    }
};
