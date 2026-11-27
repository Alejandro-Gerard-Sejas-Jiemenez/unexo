<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Spec 017: atajo a una nueva matrícula con el estudiante elegido. */
class MatriculasAtajosTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(Rol $rol, array $datos = []): User
    {
        return User::factory()->create($datos + ['rol' => $rol, 'estado' => 'activo']);
    }

    public function test_ca06_las_opciones_incluyen_al_estudiante_pedido(): void
    {
        config(['limites.opciones_select' => 1]);
        $this->usuario(Rol::Estudiante, ['apellidos' => 'AAA']);
        $pedido = $this->usuario(Rol::Estudiante, ['apellidos' => 'ZZZ']);
        $inactivo = $this->usuario(Rol::Estudiante, ['apellidos' => 'YYY', 'estado' => 'inactivo']);
        $this->actingAs($this->usuario(Rol::Administrador));

        $ids = fn (string $consulta) => array_column($this->getJson("/api/panel/matriculas/opciones{$consulta}")->assertOk()->json('estudiantes'), 'id');

        $this->assertNotContains($pedido->id, $ids(''));
        $this->assertContains($pedido->id, $ids("?estudiante_id={$pedido->id}"));
        $this->assertNotContains($inactivo->id, $ids("?estudiante_id={$inactivo->id}"));
        $this->assertCount(1, $ids('?estudiante_id=no-valido'));
    }
}
