<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Support\RestriccionCheck;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('codigo', 30)->unique();
            $table->string('nombre');
            $table->string('version', 20)->default('1.0');
            $table->text('descripcion')->nullable();
            $table->string('estado_comercial', 20)->default('borrador');
            $table->timestamps();

            $table->unique(['nombre', 'version']);
        });

        RestriccionCheck::agregar('programas', 'programas_estado_check', "estado_comercial IN ('borrador','abierto','cerrado')");
    }

    public function down(): void
    {
        Schema::dropIfExists('programas');
    }
};
