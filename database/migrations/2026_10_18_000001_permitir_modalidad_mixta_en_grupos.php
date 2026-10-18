<?php

use App\Support\RestriccionCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE grupos DROP CONSTRAINT IF EXISTS grupos_modalidad_check');
            RestriccionCheck::agregar('grupos', 'grupos_modalidad_check', "modalidad IN ('virtual','presencial','mixta')");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE grupos DROP CONSTRAINT IF EXISTS grupos_modalidad_check');
            RestriccionCheck::agregar('grupos', 'grupos_modalidad_check', "modalidad IN ('virtual','presencial')");
        }
    }
};
