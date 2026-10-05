<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class RestriccionCheck
{
    // SQLite (pruebas) no admite ALTER TABLE ... ADD CONSTRAINT; en PostgreSQL el CHECK sí se aplica.
    public static function agregar(string $tabla, string $nombre, string $condicion): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE {$tabla} ADD CONSTRAINT {$nombre} CHECK ({$condicion})");
        }
    }
}
