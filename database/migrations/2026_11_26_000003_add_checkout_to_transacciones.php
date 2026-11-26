<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transacciones', function (Blueprint $table) {
            $table->string('stripe_checkout_session_id', 255)->nullable()->unique();
            // Cuántas veces se inició el cargo en Stripe; mayor que cero prohíbe resolverlo a mano.
            $table->unsignedSmallInteger('stripe_intento')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('transacciones', function (Blueprint $table) {
            $table->dropUnique(['stripe_checkout_session_id']);
            $table->dropColumn(['stripe_checkout_session_id', 'stripe_intento']);
        });
    }
};
