<?php

namespace Tests\Feature;

use App\Enums\Certificado;
use App\Models\Programa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// Spec 009: RF-04, RF-05, CA-50
class EsquemaProgramasTest extends TestCase
{
    use RefreshDatabase;

    private function migracion(): object
    {
        return require database_path('migrations/2026_10_20_000003_ajustar_campos_de_programas.php');
    }

    private function programa(string $codigo, ?string $certificado): string
    {
        $programa = Programa::create(['codigo' => $codigo, 'nombre' => 'DEMO '.$codigo, 'version' => '1']);
        DB::table('programas')->where('id', $programa->id)->update(['certificado_otorga' => $certificado]);

        return $programa->id;
    }

    private function crudo(string $id): ?string
    {
        return DB::table('programas')->where('id', $id)->value('certificado_otorga');
    }

    public function test_programas_ya_no_tiene_nivel_academico_ni_perfil_de_egreso(): void
    {
        $this->assertFalse(Schema::hasColumn('programas', 'nivel_academico'));
        $this->assertFalse(Schema::hasColumn('programas', 'perfil_egreso'));
        $this->assertTrue(Schema::hasColumn('programas', 'requisitos_ingreso'));
        $this->assertTrue(Schema::hasColumn('programas', 'certificado_otorga'));
    }

    public function test_el_modelo_no_acepta_los_campos_retirados(): void
    {
        $fillable = (new Programa)->getFillable();

        $this->assertNotContains('nivel_academico', $fillable);
        $this->assertNotContains('perfil_egreso', $fillable);
        $this->assertContains('certificado_otorga', $fillable);
        $this->assertContains('requisitos_ingreso', $fillable);
    }

    public function test_up_normaliza_solo_los_textos_que_son_exactamente_las_etiquetas(): void
    {
        $a = $this->programa('DEMO-P1', 'Certificado');
        $b = $this->programa('DEMO-P2', ' sin CERTIFICADO ');
        $c = $this->programa('DEMO-P3', 'Certificado de asistencia');
        $d = $this->programa('DEMO-P4', null);

        $this->migracion()->up();

        $this->assertSame('certificado', $this->crudo($a));
        $this->assertSame('sin_certificado', $this->crudo($b));
        $this->assertSame('Certificado de asistencia', $this->crudo($c));
        $this->assertNull($this->crudo($d));
    }

    // CA-50
    public function test_un_texto_antiguo_se_conserva_y_se_lee_como_no_definido(): void
    {
        $id = $this->programa('DEMO-P5', 'Diploma de la UAGRM');

        $this->migracion()->up();

        $this->assertSame('Diploma de la UAGRM', $this->crudo($id));
        $this->assertNull(Programa::find($id)->certificado_otorga);
    }

    public function test_up_es_idempotente_si_las_columnas_ya_no_existen(): void
    {
        $this->migracion()->up();

        $this->assertFalse(Schema::hasColumn('programas', 'nivel_academico'));
    }

    public function test_down_recrea_columnas_vacias_y_restaura_las_etiquetas(): void
    {
        $a = $this->programa('DEMO-P6', 'certificado');
        $b = $this->programa('DEMO-P7', 'sin_certificado');
        $c = $this->programa('DEMO-P8', 'Texto libre');

        $this->migracion()->down();

        $this->assertTrue(Schema::hasColumn('programas', 'nivel_academico'));
        $this->assertTrue(Schema::hasColumn('programas', 'perfil_egreso'));
        $this->assertSame('Certificado', $this->crudo($a));
        $this->assertSame('Sin certificado', $this->crudo($b));
        $this->assertSame('Texto libre', $this->crudo($c));
        $this->assertNull(DB::table('programas')->where('id', $a)->value('nivel_academico'));
    }

    public function test_el_atributo_certificado_se_lee_y_se_escribe_con_el_enum(): void
    {
        $programa = Programa::create([
            'codigo' => 'DEMO-P9', 'nombre' => 'DEMO P9', 'version' => '1',
            'certificado_otorga' => Certificado::Certificado,
        ]);
        $this->assertSame('certificado', $this->crudo($programa->id));
        $this->assertSame(Certificado::Certificado, $programa->fresh()->certificado_otorga);

        $programa->update(['certificado_otorga' => 'sin_certificado']);
        $this->assertSame(Certificado::SinCertificado, $programa->fresh()->certificado_otorga);

        $programa->update(['certificado_otorga' => null]);
        $this->assertNull($programa->fresh()->certificado_otorga);
        $this->assertNull($this->crudo($programa->id));
    }
}
