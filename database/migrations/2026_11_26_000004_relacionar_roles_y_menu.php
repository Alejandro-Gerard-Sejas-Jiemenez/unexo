<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Sin los roles base no se podría crear ningún usuario. Los que ya existan no se tocan.
        $ahora = now();
        foreach (['administrador' => 'Administrador', 'docente' => 'Docente', 'estudiante' => 'Estudiante'] as $clave => $nombre) {
            DB::table('roles')->insertOrIgnore([
                'id' => (string) Str::uuid(),
                'clave' => $clave,
                'nombre' => $nombre,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('rol')->references('clave')->on('roles')->restrictOnDelete();
        });

        // Se restringe el borrado: dejar el permiso en nulo haría la opción visible para todos.
        Schema::table('menu_items', function (Blueprint $table) {
            $table->foreign('permiso_clave')->references('clave')->on('permisos')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropForeign(['permiso_clave']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['rol']);
        });
    }
};
