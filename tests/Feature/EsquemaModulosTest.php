<?php

namespace Tests\Feature;

use App\Models\Modulo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EsquemaModulosTest extends TestCase
{
    use RefreshDatabase;

    public function test_modulos_ya_no_tiene_creditos_ni_tipo_de_evaluacion(): void
    {
        $this->assertFalse(Schema::hasColumn('modulos', 'creditos'));
        $this->assertFalse(Schema::hasColumn('modulos', 'tipo_evaluacion'));
    }

    public function test_modulos_tiene_prerrequisito(): void
    {
        $this->assertTrue(Schema::hasColumn('modulos', 'prerrequisito'));
    }

    public function test_un_modulo_se_crea_con_prerrequisito_y_sin_el(): void
    {
        $con = Modulo::create([
            'codigo' => 'DEMO-M90',
            'nombre' => 'DEMO Módulo con prerrequisito',
            'duracion_horas' => 20,
            'prerrequisito' => 'Lógica de programación básica',
        ]);
        $sin = Modulo::create([
            'codigo' => 'DEMO-M91',
            'nombre' => 'DEMO Módulo sin prerrequisito',
            'duracion_horas' => 20,
        ]);

        $this->assertSame('Lógica de programación básica', $con->fresh()->prerrequisito);
        $this->assertNull($sin->fresh()->prerrequisito);
    }

    public function test_el_modelo_no_acepta_los_campos_retirados(): void
    {
        $fillable = (new Modulo)->getFillable();

        $this->assertContains('prerrequisito', $fillable);
        $this->assertNotContains('creditos', $fillable);
        $this->assertNotContains('tipo_evaluacion', $fillable);
    }
}
