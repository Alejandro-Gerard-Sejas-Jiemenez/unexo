<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('stripe_customer_id', 100)->nullable()->index();
        });

        Schema::table('metodos_pago', function (Blueprint $table) {
            $table->string('proveedor', 20)->default('simulado');
            $table->string('stripe_payment_method_id', 100)->nullable()->index();
            $table->string('marca', 30)->nullable();
            $table->unsignedSmallInteger('exp_mes')->nullable();
            $table->unsignedSmallInteger('exp_anio')->nullable();
        });

        Schema::table('transacciones', function (Blueprint $table) {
            $table->string('proveedor', 20)->default('simulado');
            $table->string('moneda', 10)->default('bob');
            $table->string('stripe_payment_intent_id', 100)->nullable()->unique();
            $table->string('stripe_charge_id', 100)->nullable();
            $table->string('motivo_rechazo', 255)->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->json('metadata')->nullable();
        });

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE transacciones ALTER COLUMN estado TYPE varchar(20)');
            DB::statement('ALTER TABLE transacciones DROP CONSTRAINT IF EXISTS transacciones_estado_check');
            DB::statement("ALTER TABLE transacciones ADD CONSTRAINT transacciones_estado_check CHECK (estado IN ('pendiente','procesando','requiere_accion','aprobada','rechazada','reembolsada','cancelada'))");
        }

        Schema::create('stripe_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('stripe_event_id', 120)->unique();
            $table->string('tipo', 80);
            $table->timestamp('procesado_en')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_events');

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE transacciones DROP CONSTRAINT IF EXISTS transacciones_estado_check');
            DB::statement("ALTER TABLE transacciones ADD CONSTRAINT transacciones_estado_check CHECK (estado IN ('pendiente','aprobada','rechazada'))");
        }

        Schema::table('transacciones', function (Blueprint $table) {
            $table->dropColumn([
                'proveedor',
                'moneda',
                'stripe_payment_intent_id',
                'stripe_charge_id',
                'motivo_rechazo',
                'fecha_vencimiento',
                'metadata',
            ]);
        });

        Schema::table('metodos_pago', function (Blueprint $table) {
            $table->dropColumn([
                'proveedor',
                'stripe_payment_method_id',
                'marca',
                'exp_mes',
                'exp_anio',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('stripe_customer_id');
        });
    }
};
