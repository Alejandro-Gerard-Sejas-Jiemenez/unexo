<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // Nulo cuando el aviso no corresponde a ningún pago.
        Schema::table('stripe_events', function (Blueprint $table) {
            $table->foreignUuid('transaccion_id')->nullable()->constrained('transacciones')->restrictOnDelete();
        });

        // Nulo en los accesos creados antes de registrar el autor.
        Schema::table('api_clientes', function (Blueprint $table) {
            $table->foreignUuid('creado_por')->nullable()->constrained('users')->nullOnDelete();
        });

        // Desde aquí los tokens son solo de los clientes de la API.
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->foreign('tokenable_id')->references('id')->on('api_clientes')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropForeign(['tokenable_id']);
        });

        Schema::table('api_clientes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('creado_por');
        });

        Schema::table('stripe_events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('transaccion_id');
        });

        Schema::table('sessions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
    }
};
