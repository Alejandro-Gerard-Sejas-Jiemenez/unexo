<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Precio con el que se cobra el módulo cuando un estudiante lo toma suelto (sin programa).
    public function up(): void
    {
        Schema::table('modulos', function (Blueprint $table) {
            $table->decimal('precio', 10, 2)->nullable()->after('duracion_horas');
        });
    }

    public function down(): void
    {
        Schema::table('modulos', function (Blueprint $table) {
            $table->dropColumn('precio');
        });
    }
};
