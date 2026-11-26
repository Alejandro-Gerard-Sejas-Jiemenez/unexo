<?php

namespace Tests\Feature;

use App\Enums\EstadoMatricula;
use App\Enums\Rol;
use App\Models\Aula;
use App\Models\Bitacora;
use App\Models\ClienteApi;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Spec 012, CA-06: el tamaño de página lo elige el usuario entre los permitidos. */
class PaginacionTamanoTest extends TestCase
{
    use RefreshDatabase;

    private const LISTAS = ['usuarios', 'catalogo/programas', 'catalogo/modulos', 'cohortes', 'grupos', 'matriculas', 'aulas', 'clientes-api', 'bitacora'];

    private function sembrar(): User
    {
        $admin = User::factory()->create(['rol' => Rol::Administrador, 'estado' => 'activo']);
        $docente = User::factory()->create(['rol' => Rol::Docente, 'estado' => 'activo']);

        foreach (range(1, 12) as $i) {
            $programa = Programa::create(['codigo' => "DEMO-P{$i}", 'nombre' => "DEMO Programa {$i}", 'version' => '1.0']);
            $modulo = Modulo::create(['codigo' => "DEMO-M{$i}", 'nombre' => "DEMO Modulo {$i}"]);
            $cohorte = Cohorte::create(['programa_id' => $programa->id, 'nombre' => "DEMO Edicion {$i}", 'fecha_inicio' => today(), 'fecha_fin' => today()->addMonth(), 'estado' => 'programado']);
            Grupo::create(['modulo_id' => $modulo->id, 'docente_id' => $docente->id, 'nombre' => "DEMO Grupo {$i}", 'modalidad' => 'virtual', 'estado' => 'programado', 'fecha_inicio' => today(), 'fecha_fin' => today()->addMonth()]);
            Aula::create(['nombre' => "DEMO Aula {$i}", 'capacidad' => 20]);
            ClienteApi::create(['nombre' => "DEMO Cliente {$i}"]);
            $estudiante = User::factory()->create(['rol' => Rol::Estudiante, 'estado' => 'activo']);
            Matricula::create(['estudiante_id' => $estudiante->id, 'cohorte_id' => $cohorte->id, 'estado' => EstadoMatricula::Pendiente, 'fecha_matricula' => today()]);
            Bitacora::create(['evento' => 'acceso_recurso', 'recurso' => "/demo/{$i}", 'ip' => '127.0.0.1']);
        }

        return $admin;
    }

    public function test_cada_lista_respeta_los_tamanos_permitidos(): void
    {
        $this->actingAs($this->sembrar());

        foreach (self::LISTAS as $lista) {
            foreach ([5, 25, 50] as $tamano) {
                $respuesta = $this->getJson("/api/panel/{$lista}?per_page={$tamano}")->assertOk();
                $total = $respuesta->json('meta.total');

                $this->assertSame($tamano, (int) $respuesta->json('meta.per_page'), "{$lista} con {$tamano}");
                $this->assertCount(min($tamano, $total), $respuesta->json('data'), "{$lista} con {$tamano}");
            }
        }
    }

    public function test_un_tamano_no_permitido_usa_diez(): void
    {
        $this->actingAs($this->sembrar());

        foreach (self::LISTAS as $lista) {
            foreach (['1000', '0', '-5', 'abc', '7', ''] as $valor) {
                $respuesta = $this->getJson("/api/panel/{$lista}?per_page={$valor}")->assertOk();

                $this->assertSame(10, (int) $respuesta->json('meta.per_page'), "{$lista} con «{$valor}»");
            }
        }
    }
}
