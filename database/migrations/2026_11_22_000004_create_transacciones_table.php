<?php

use App\Support\RestriccionCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transacciones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('matricula_id')->constrained('matriculas')->restrictOnDelete();
            $table->foreignUuid('metodo_pago_id')->nullable()->constrained('metodos_pago')->nullOnDelete();
            $table->decimal('monto', 10, 2);
            // Nulo en un pago único; con valor, es la cuota N de un plan de pagos.
            $table->unsignedSmallInteger('numero_cuota')->nullable();
            $table->unsignedSmallInteger('total_cuotas')->nullable();
            $table->string('estado', 12)->default('pendiente');
            $table->string('referencia', 40)->unique();
            $table->timestamp('fecha_proceso')->nullable();
            $table->foreignUuid('registrada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('matricula_id');
            $table->index('estado');
        });

        RestriccionCheck::agregar(
            'transacciones',
            'transacciones_estado_check',
            "estado IN ('pendiente','aprobada','rechazada')",
        );
        RestriccionCheck::agregar('transacciones', 'transacciones_monto_check', 'monto > 0');
    }

    public function down(): void
    {
        Schema::dropIfExists('transacciones');
    }
};
