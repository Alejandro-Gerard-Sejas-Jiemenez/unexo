<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Programa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginacionYLimitesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['rol' => Rol::Administrador]);
    }

    public function test_el_listado_se_pagina_segun_la_configuracion(): void
    {
        foreach (range(1, 12) as $n) {
            Programa::create(['codigo' => sprintf('P-%02d', $n), 'nombre' => sprintf('Programa %02d', $n), 'version' => '1.0']);
        }
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('programas.index'))
            ->assertOk()
            ->assertSee('Programa 10')
            ->assertDontSee('Programa 11')
            ->assertSee('1–10')
            ->assertSee('Siguiente');

        $this->actingAs($admin)->get(route('programas.index', ['page' => 2]))
            ->assertSee('Programa 11')
            ->assertSee('Programa 12')
            ->assertDontSee('Programa 10')
            ->assertSee('11–12');
    }

    public function test_el_cambio_de_pagina_conserva_la_busqueda(): void
    {
        foreach (range(1, 12) as $n) {
            Programa::create(['codigo' => sprintf('DA-%02d', $n), 'nombre' => sprintf('Datos %02d', $n), 'version' => '1.0']);
        }
        Programa::create(['codigo' => 'X-1', 'nombre' => 'Otro programa', 'version' => '1.0']);

        $this->actingAs($this->admin())->get(route('programas.index', ['q' => 'Datos']))
            ->assertDontSee('Otro programa')
            ->assertSee('q=Datos', false);
    }

    public function test_el_limite_por_pagina_se_puede_cambiar_en_un_solo_lugar(): void
    {
        config(['limites.por_pagina' => 3]);
        foreach (range(1, 5) as $n) {
            Programa::create(['codigo' => "P-$n", 'nombre' => "Programa $n", 'version' => '1.0']);
        }

        $this->actingAs($this->admin())->get(route('programas.index'))
            ->assertSee('Programa 3')
            ->assertDontSee('Programa 4')
            ->assertSee('1–3');
    }

    public function test_el_backend_rechaza_textos_mas_largos_que_el_limite(): void
    {
        $datos = [
            'rol' => Rol::Estudiante->value,
            'nombres' => str_repeat('a', config('limites.usuarios.nombres') + 1),
            'apellidos' => 'Perez',
            'email' => 'nuevo@nexo.test',
            'password' => 'Demo12345',
        ];

        $this->actingAs($this->admin())->post(route('usuarios.store'), $datos)
            ->assertSessionHasErrors('nombres');

        $datos['nombres'] = str_repeat('a', config('limites.usuarios.nombres'));
        $this->actingAs($this->admin())->post(route('usuarios.store'), $datos)
            ->assertSessionHasNoErrors();
    }

    public function test_el_formulario_refleja_los_mismos_limites_del_backend(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('usuarios.create'))
            ->assertSee('maxlength='.config('limites.usuarios.nombres'), false)
            ->assertSee('maxlength='.config('limites.usuarios.email'), false);

        $this->actingAs($admin)->get(route('programas.create'))
            ->assertSee('maxlength="'.config('limites.programas.descripcion').'"', false)
            ->assertSee('0/'.config('limites.programas.descripcion'));

        $this->actingAs($admin)->get(route('modulos.create'))
            ->assertSee('max='.config('limites.modulos.duracion_horas'), false);
    }

    public function test_los_selects_se_recortan_y_el_formulario_avisa(): void
    {
        config(['limites.opciones_select' => 2]);
        User::factory()->count(3)->create(['rol' => Rol::Estudiante]);

        $this->actingAs($this->admin())->get(route('matriculas.create'))
            ->assertOk()
            ->assertSee('Se muestran las primeras 2 de 3 opciones.');
    }

    public function test_las_paginas_incluyen_la_pantalla_de_carga(): void
    {
        $this->actingAs($this->admin())->get(route('panel'))
            ->assertSee('id="cargando"', false)
            ->assertSee('id="cargando-barra"', false);
    }

    public function test_todos_los_formularios_usan_los_mismos_botones_guardar_y_cancelar(): void
    {
        $admin = $this->admin();

        foreach (['usuarios.create', 'programas.create', 'modulos.create', 'cohortes.create', 'grupos.create', 'matriculas.create'] as $ruta) {
            $this->actingAs($admin)->get(route($ruta))
                ->assertSee('class="form-acciones"', false)
                ->assertSee('btn btn-secundario', false)
                ->assertSee('btn btn-primario', false)
                ->assertSee('Cancelar');
        }

        $this->actingAs($admin)->get(route('matriculas.create'))->assertSee('Registrar matrícula');
    }
}
