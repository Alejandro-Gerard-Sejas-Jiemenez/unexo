<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Aula;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Spec 016: datos de un aula para su página de edición. */
class AulaDetalleTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(Rol $rol): User
    {
        return User::factory()->create(['rol' => $rol, 'estado' => 'activo']);
    }

    public function test_ca01_el_administrador_consulta_un_aula(): void
    {
        $aula = Aula::create(['nombre' => 'DEMO Aula 1', 'capacidad' => 30]);
        $this->actingAs($this->usuario(Rol::Administrador));

        $this->getJson("/api/panel/aulas/{$aula->id}")
            ->assertOk()
            ->assertExactJson(['data' => ['id' => $aula->id, 'nombre' => 'DEMO Aula 1', 'capacidad' => 30]]);

        $this->getJson('/api/panel/aulas/'.Str::uuid())->assertNotFound();
    }

    public function test_ca02_solo_el_administrador_puede_consultarla(): void
    {
        $aula = Aula::create(['nombre' => 'DEMO Aula 1']);

        $this->getJson("/api/panel/aulas/{$aula->id}")->assertUnauthorized();

        foreach ([Rol::Docente, Rol::Estudiante] as $rol) {
            $this->actingAs($this->usuario($rol))->getJson("/api/panel/aulas/{$aula->id}")->assertForbidden();
        }
    }
}
