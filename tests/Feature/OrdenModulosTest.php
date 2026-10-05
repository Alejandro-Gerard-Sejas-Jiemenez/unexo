<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrdenModulosTest extends TestCase
{
    use RefreshDatabase;

    private function programaConModulos(int $cantidad): array
    {
        $programa = Programa::create(['codigo' => 'P-1', 'nombre' => 'Diplomado', 'version' => '1.0']);
        $modulos = [];
        foreach (range(1, $cantidad) as $n) {
            $modulos[] = $m = Modulo::create(['codigo' => "M-$n", 'nombre' => "Módulo $n"]);
            $this->actingAs(User::factory()->create(['rol' => Rol::Administrador]))
                ->post(route('programas.modulos.agregar', $programa), ['modulo_id' => $m->id]);
        }

        return [$programa, $modulos];
    }

    private function orden(Programa $programa): array
    {
        return $programa->modulos()->orderBy('programa_modulo.orden')->pluck('modulos.codigo')->all();
    }

    public function test_los_modulos_se_agregan_al_final_y_no_se_repiten(): void
    {
        [$programa, $modulos] = $this->programaConModulos(3);

        $this->assertSame(['M-1', 'M-2', 'M-3'], $this->orden($programa));

        $this->post(route('programas.modulos.agregar', $programa), ['modulo_id' => $modulos[0]->id])
            ->assertSessionHasErrors('modulo_id');
    }

    public function test_subir_y_bajar_intercambian_con_el_vecino(): void
    {
        [$programa, $modulos] = $this->programaConModulos(3);

        $this->patch(route('programas.modulos.mover', [$programa, $modulos[2]]), ['direccion' => 'subir']);
        $this->assertSame(['M-1', 'M-3', 'M-2'], $this->orden($programa));

        $this->patch(route('programas.modulos.mover', [$programa, $modulos[0]]), ['direccion' => 'bajar']);
        $this->assertSame(['M-3', 'M-1', 'M-2'], $this->orden($programa));

        // El último módulo (M-2) no puede bajar más: no cambia nada.
        $this->patch(route('programas.modulos.mover', [$programa, $modulos[1]]), ['direccion' => 'bajar']);
        $this->assertSame(['M-3', 'M-1', 'M-2'], $this->orden($programa));
    }

    public function test_al_quitar_un_modulo_el_orden_se_renumera_sin_huecos(): void
    {
        [$programa, $modulos] = $this->programaConModulos(3);

        $this->delete(route('programas.modulos.quitar', [$programa, $modulos[0]]));

        $this->assertSame(['M-2', 'M-3'], $this->orden($programa));
        $this->assertSame([1, 2], $programa->modulos()->orderBy('programa_modulo.orden')->get()->map(fn ($m) => $m->pivot->orden)->all());
    }
}
