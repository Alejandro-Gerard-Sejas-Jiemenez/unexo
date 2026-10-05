<?php

use App\Support\RestriccionCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matriculas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('estudiante_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('cohorte_id')->nullable()->constrained('cohortes')->restrictOnDelete();
            $table->string('estado', 15)->default('pendiente');
            $table->string('origen', 10)->default('manual');
            $table->string('id_externo_crm', 60)->nullable();
            $table->date('fecha_matricula');
            $table->date('fecha_retiro')->nullable();
            $table->timestamps();

            $table->index('estudiante_id');
        });

        RestriccionCheck::agregar('matriculas', 'matriculas_estado_check', "estado IN ('pendiente','activo','retirado')");
        RestriccionCheck::agregar('matriculas', 'matriculas_origen_check', "origen IN ('crm','manual')");
    }

    public function down(): void
    {
        Schema::dropIfExists('matriculas');
    }
};
