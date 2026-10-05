<?php

namespace Tests\Feature;

use App\Enums\EstadoComercial;
use App\Enums\Rol;
use App\Models\Modulo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Decisión del equipo: los módulos son totalmente libres (sin requisitos y sueltos o en programa, da igual). */
class ModulosLibresTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['rol' => Rol::Administrador]);
    }

    public function test_el_formulario_de_modulo_no_pide_requisitos_ni_marca_de_independiente(): void
    {
        $this->actingAs($this->admin())->get(route('modulos.create'))
            ->assertOk()
            ->assertDontSee('Requisitos')
            ->assertDontSee('tomar por separado');
    }

    public function test_se_puede_crear_un_modulo_sin_programa_ni_requisitos(): void
    {
        $this->actingAs($this->admin())->post(route('modulos.store'), [
            'codigo' => 'M-1',
            'nombre' => 'SQL para análisis',
            'estado_comercial' => EstadoComercial::Abierto->value,
        ])->assertSessionHasNoErrors()->assertRedirect(route('modulos.index'));

        $this->assertDatabaseHas('modulos', ['codigo' => 'M-1']);
    }

    public function test_un_modulo_abierto_sin_programa_aparece_en_el_catalogo(): void
    {
        Modulo::create(['codigo' => 'M-1', 'nombre' => 'Módulo libre', 'estado_comercial' => EstadoComercial::Abierto]);
        Modulo::create(['codigo' => 'M-2', 'nombre' => 'Módulo en borrador', 'estado_comercial' => EstadoComercial::Borrador]);

        $this->actingAs($this->admin())->get(route('catalogo'))
            ->assertSee('Módulo libre')
            ->assertDontSee('Módulo en borrador');

        $this->assertSame(1, Modulo::visibleParaCrm()->count());
    }
}
