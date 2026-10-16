<?php

use App\Support\RestriccionCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comprobantes_pago', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('matricula_id')->constrained('matriculas')->restrictOnDelete();
            $table->string('archivo_ruta');
            $table->string('estado', 12)->default('pendiente');
            $table->foreignUuid('subido_por')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('verificado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('fecha_verificacion')->nullable();
            $table->timestamps();

            $table->index('matricula_id');
        });

        RestriccionCheck::agregar('comprobantes_pago', 'comprobantes_estado_check', "estado IN ('pendiente','aprobado','rechazado')");
    }

    public function down(): void
    {
        Schema::dropIfExists('comprobantes_pago');
    }
};