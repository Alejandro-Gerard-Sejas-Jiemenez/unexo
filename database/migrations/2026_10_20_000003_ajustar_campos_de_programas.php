<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * Spec 009: el programa deja de pedir nivel académico y perfil de egreso, y el certificado pasa a ser una opción fija.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Solo las que existan: la base compartida puede diferir.
        $porQuitar = array_values(array_filter(
            ['nivel_academico', 'perfil_egreso'],
            fn ($c) => Schema::hasColumn('programas', $c),
        ));

        if ($porQuitar !== []) {
            Schema::table('programas', function (Blueprint $table) use ($porQuitar) {
                $table->dropColumn($porQuitar);
            });
        }

        // Cualquier otro texto antiguo se conserva: el modelo lo lee como "no definido".
        $this->reemplazar('certificado', 'certificado');
        $this->reemplazar('sin certificado', 'sin_certificado');
    }

    public function down(): void
    {
        if (! Schema::hasColumn('programas', 'nivel_academico')) {
            Schema::table('programas', function (Blueprint $table) {
                $table->string('nivel_academico')->nullable();
            });
        }

        if (! Schema::hasColumn('programas', 'perfil_egreso')) {
            Schema::table('programas', function (Blueprint $table) {
                $table->text('perfil_egreso')->nullable();
            });
        }

        DB::table('programas')->where('certificado_otorga', 'certificado')->update(['certificado_otorga' => 'Certificado']);
        DB::table('programas')->where('certificado_otorga', 'sin_certificado')->update(['certificado_otorga' => 'Sin certificado']);
    }

    private function reemplazar(string $etiqueta, string $valor): void
    {
        DB::table('programas')
            ->whereRaw('LOWER(TRIM(certificado_otorga)) = ?', [$etiqueta])
            ->update(['certificado_otorga' => $valor]);
    }
};
