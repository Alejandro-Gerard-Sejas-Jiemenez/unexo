<?php

namespace Tests\Feature;

use App\Enums\EstadoComercial;
use App\Enums\EstadoPlanificacion;
use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Models\Aula;
use App\Models\ClienteApi;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmCatalogoApiTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(): array
    {
        $cliente = ClienteApi::create(['nombre' => 'CRM Ventas']);

        return [$cliente, ['Authorization' => 'Bearer '.$cliente->createToken('crm')->plainTextToken]];
    }

    public function test_catalogo_crm_exige_autenticacion(): void
    {
        $this->getJson('/api/v1/crm/catalogo')->assertUnauthorized();
        $this->getJson('/api/v1/crm/programas')->assertUnauthorized();
        $this->getJson('/api/v1/crm/modulos')->assertUnauthorized();
    }

    public function test_catalogo_crm_retorna_oferta_abierta(): void
    {
        [$cliente, $headers] = $this->cliente();

        $docente = User::factory()->create(['rol' => Rol::Docente, 'nombres' => 'Carlos', 'apellidos' => 'Docente']);
        $aula = Aula::create(['nombre' => 'Aula 101', 'capacidad' => 30]);

        // Programa abierto con 2 módulos y una edición
        $programa = Programa::create([
            'codigo' => 'DIP-AI',
            'nombre' => 'Diplomado en Inteligencia Artificial',
            'version' => '1.0',
            'descripcion' => 'Formación especializada en IA moderna.',
            'estado_comercial' => EstadoComercial::Abierto,
            'precio' => 4500.00,
            'nivel_academico' => 'Postgrado',
            'certificado_otorga' => 'Certificado de Diplomado',
        ]);

        $modulo1 = Modulo::create([
            'codigo' => 'MOD-101',
            'nombre' => 'Fundamentos de Machine Learning',
            'duracion_horas' => 40,
            'creditos' => 4,
            'estado_comercial' => EstadoComercial::Abierto,
        ]);

        $modulo2 = Modulo::create([
            'codigo' => 'MOD-102',
            'nombre' => 'Redes Neuronales Profundas',
            'duracion_horas' => 50,
            'creditos' => 5,
            'estado_comercial' => EstadoComercial::Abierto,
        ]);

        $programa->modulos()->attach([
            $modulo1->id => ['orden' => 1],
            $modulo2->id => ['orden' => 2],
        ]);

        $edicion = Cohorte::create([
            'programa_id' => $programa->id,
            'nombre' => 'Edición 1',
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => '2026-12-15',
            'estado' => EstadoPlanificacion::Programado,
        ]);

        Grupo::create([
            'modulo_id' => $modulo1->id,
            'cohorte_id' => $edicion->id,
            'docente_id' => $docente->id,
            'aula_id' => $aula->id,
            'nombre' => 'Grupo A',
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => '2026-10-15',
            'modalidad' => Modalidad::Presencial,
            'estado' => EstadoPlanificacion::Programado,
        ]);

        // Programa en borrador no debe aparecer
        Programa::create([
            'codigo' => 'BORRADOR',
            'nombre' => 'Programa Oculto',
            'version' => '1.0',
            'estado_comercial' => EstadoComercial::Borrador,
        ]);

        // Consulta catálogo completo
        $respuesta = $this->getJson('/api/v1/crm/catalogo', $headers)
            ->assertOk()
            ->assertJsonPath('exito', true)
            ->assertJsonPath('resumen.total_programas_abiertos', 1);

        $datos = $respuesta->json();
        $this->assertCount(1, $datos['programas']);
        $this->assertEquals('DIP-AI', $datos['programas'][0]['codigo']);
        $this->assertEquals(90, $datos['programas'][0]['duracion_total_horas']);
        $this->assertEquals(9, $datos['programas'][0]['total_creditos']);
        $this->assertCount(2, $datos['programas'][0]['modulos']);
        $this->assertCount(1, $datos['programas'][0]['ediciones_disponibles']);
        $this->assertEquals('Edición 1', $datos['programas'][0]['ediciones_disponibles'][0]['nombre']);
    }

    public function test_filtro_y_detalle_de_programas(): void
    {
        [$cliente, $headers] = $this->cliente();

        $prog1 = Programa::create(['codigo' => 'P1', 'nombre' => 'Big Data Analytics', 'version' => '1.0', 'estado_comercial' => EstadoComercial::Abierto]);
        $m1 = Modulo::create(['codigo' => 'M1', 'nombre' => 'Hadoop', 'estado_comercial' => EstadoComercial::Abierto]);
        $prog1->modulos()->attach($m1->id, ['orden' => 1]);

        $prog2 = Programa::create(['codigo' => 'P2', 'nombre' => 'Ciberseguridad', 'version' => '1.0', 'estado_comercial' => EstadoComercial::Abierto]);
        $m2 = Modulo::create(['codigo' => 'M2', 'nombre' => 'Hacking Ético', 'estado_comercial' => EstadoComercial::Abierto]);
        $prog2->modulos()->attach($m2->id, ['orden' => 1]);

        // Filtro por texto
        $this->getJson('/api/v1/crm/programas?q=Data', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.codigo', 'P1');

        // Detalle por ID
        $this->getJson("/api/v1/crm/programas/{$prog1->id}", $headers)
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Big Data Analytics');
    }

    public function test_modulos_independientes(): void
    {
        [$cliente, $headers] = $this->cliente();

        $modulo = Modulo::create([
            'codigo' => 'CURSO-PY',
            'nombre' => 'Python para Negocios',
            'duracion_horas' => 24,
            'creditos' => 2,
            'estado_comercial' => EstadoComercial::Abierto,
        ]);

        $this->getJson('/api/v1/crm/modulos', $headers)
            ->assertOk()
            ->assertJsonPath('data.0.codigo', 'CURSO-PY');

        $this->getJson("/api/v1/crm/modulos/{$modulo->id}", $headers)
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Python para Negocios');
    }
}
