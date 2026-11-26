<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('clave', 30)->unique();
            $table->string('nombre', 60);
            $table->string('descripcion', 200)->nullable();
            $table->timestamps();
        });

        Schema::create('permisos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('clave', 80)->unique();
            $table->string('nombre', 100);
            $table->string('modulo', 40);
            $table->timestamps();
        });

        Schema::create('rol_permiso', function (Blueprint $table) {
            $table->foreignUuid('rol_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignUuid('permiso_id')->constrained('permisos')->cascadeOnDelete();
            $table->primary(['rol_id', 'permiso_id']);
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('parent_id')->nullable();
            $table->string('etiqueta', 80);
            $table->string('ruta', 120)->nullable();
            $table->string('icono', 40)->nullable();
            $table->string('permiso_clave', 80)->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['activo', 'orden']);
            $table->index('permiso_clave');
        });

        Schema::table('menu_items', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('menu_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('rol_permiso');
        Schema::dropIfExists('permisos');
        Schema::dropIfExists('roles');
    }
};
