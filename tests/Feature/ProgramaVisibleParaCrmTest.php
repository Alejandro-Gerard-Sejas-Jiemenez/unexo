<?php

namespace Tests\Feature;

use App\Enums\EstadoComercial;
use App\Models\Modulo;
use App\Models\Programa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramaVisibleParaCrmTest extends TestCase
{
    use RefreshDatabase;

    private function programa(string $codigo, EstadoComercial $estado): Programa
    {
        return Programa::create(['codigo' => $codigo, 'nombre' => "Programa $codigo", 'version' => '1.0', 'estado_comercial' => $estado]);
    }

    private function modulo(string $codigo, EstadoComercial $estado): Modulo
    {
        return Modulo::create(['codigo' => $codigo, 'nombre' => "Módulo $codigo", 'estado_comercial' => $estado]);
    }

    // RF-29 / CA-29
    public function test_solo_es_visible_el_programa_abierto_con_al_menos_un_modulo_abierto(): void
    {
        $visible = $this->programa('P-1', EstadoComercial::Abierto);
        $visible->modulos()->attach($this->modulo('M-1', EstadoComercial::Abierto)->id, ['orden' => 1]);

        $vacio = $this->programa('P-2', EstadoComercial::Abierto);
        $vacio->modulos()->attach($this->modulo('M-2', EstadoComercial::Cerrado)->id, ['orden' => 1]);

        $borrador = $this->programa('P-3', EstadoComercial::Borrador);
        $borrador->modulos()->attach($this->modulo('M-3', EstadoComercial::Abierto)->id, ['orden' => 1]);

        $this->programa('P-4', EstadoComercial::Abierto);

        $ids = Programa::visibleParaCrm()->pluck('id')->all();

        $this->assertSame([$visible->id], $ids);
    }

    // RF-15: los programas abiertos sin módulos abiertos no son visibles y el CRM los ve vacíos.
    public function test_los_programas_vacios_no_son_visibles_pero_si_se_listan_como_vacios(): void
    {
        $vacio = $this->programa('P-1', EstadoComercial::Abierto);

        $this->assertSame(0, Programa::visibleParaCrm()->count());
        $this->assertSame([$vacio->id], Programa::abiertoSinModulosAbiertos()->pluck('id')->all());
    }
}
