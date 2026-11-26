<?php

use App\Support\RestriccionCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacora', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Nulo en los intentos fallidos de inicio de sesión: ahí no hay usuario.
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('evento', 30);
            $table->string('identificador', 255)->nullable();
            $table->string('recurso', 255)->nullable();
            $table->string('metodo_http', 10)->nullable();
            $table->unsignedSmallInteger('codigo_respuesta')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('user_id');
            $table->index('evento');
            $table->index('created_at');
            $table->index('recurso');
        });

        RestriccionCheck::agregar(
            'bitacora',
            'bitacora_evento_check',
            "evento IN ('login_exitoso','login_fallido','logout','acceso_recurso')",
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora');
    }
};
