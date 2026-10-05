<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Support\RestriccionCheck;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('rol', 20);
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('documento_identidad', 30)->nullable()->unique();
            $table->string('email')->unique();
            $table->string('correo_institucional')->nullable()->unique();
            $table->string('telefono', 30)->nullable();
            $table->string('password');
            $table->string('id_externo_crm')->nullable()->unique();
            $table->string('estado', 20)->default('activo');
            $table->rememberToken();
            $table->timestamps();
        });

        RestriccionCheck::agregar('users', 'users_rol_check', "rol IN ('administrador','docente','estudiante')");
        RestriccionCheck::agregar('users', 'users_estado_check', "estado IN ('activo','inactivo')");

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
