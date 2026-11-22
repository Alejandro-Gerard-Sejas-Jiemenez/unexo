<?php

use App\Support\RestriccionCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metodos_pago', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo', 20);
            $table->string('titular', 120);
            // Nunca se guarda el número completo de la tarjeta: solo los últimos cuatro.
            $table->string('ultimos_digitos', 4)->nullable();
            $table->string('vencimiento', 5)->nullable();
            $table->string('etiqueta', 60)->nullable();
            $table->boolean('predeterminado')->default(false);
            $table->timestamps();

            $table->index('user_id');
        });

        RestriccionCheck::agregar(
            'metodos_pago',
            'metodos_pago_tipo_check',
            "tipo IN ('tarjeta','qr','transferencia')",
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('metodos_pago');
    }
};
