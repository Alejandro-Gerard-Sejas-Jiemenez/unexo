<?php

namespace Tests\Feature;

use App\Enums\EstadoComercial;
use App\Enums\EstadoComprobante;
use App\Enums\EstadoMatricula;
use App\Enums\EstadoPlanificacion;
use App\Enums\OrigenMatricula;
use App\Enums\Rol;
use App\Models\ClienteApi;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MatriculasApiTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(): array
    {
        $cliente = ClienteApi::create(['nombre' => 'CRM de prueba']);

        return [$cliente, ['Authorization' => 'Bearer '.$cliente->createToken('crm')->plainTextToken]];
    }

    private function cohorte(): Cohorte
    {
        $programa = Programa::create(['codigo' => 'P-1', 'nombre' => 'Diplomado', 'version' => '1.0', 'estado_comercial' => EstadoComercial::Abierto]);
        $cohorte = Cohorte::create(['programa_id' => $programa->id, 'nombre' => 'C1', 'fecha_inicio' => '2026-09-01', 'fecha_fin' => '2026-12-01', 'estado' => EstadoPlanificacion::Programado]);
        $docente = User::factory()->create(['rol' => Rol::Docente]);
        $modulo = Modulo::create(['codigo' => 'M-1', 'nombre' => 'Excel', 'estado_comercial' => EstadoComercial::Abierto]);
        Grupo::create(['modulo_id' => $modulo->id, 'cohorte_id' => $cohorte->id, 'docente_id' => $docente->id, 'nombre' => 'A', 'modalidad' => 'virtual', 'estado' => EstadoPlanificacion::Programado]);

        return $cohorte;
    }

    private function convertido(array $extra = []): array
    {
        return $extra + [
            'id_externo_crm' => 'CRM-100',
            'nombres' => 'Ana',
            'apellidos' => 'Rojas',
            'email' => 'ana@example.test',
            'documento_identidad' => '1234567',
        ];
    }

    public function test_la_api_exige_token(): void
    {
        $this->postJson('/api/v1/estudiantes-convertidos', $this->convertido())->assertUnauthorized();
        $this->getJson('/api/v1/matriculas')->assertUnauthorized();
    }

    public function test_recibir_alumno_convertido_crea_alumno_y_matricula_crm(): void
    {
        [, $cabecera] = $this->cliente();
        $cohorte = $this->cohorte();

        $this->postJson('/api/v1/estudiantes-convertidos', $this->convertido(['cohorte_id' => $cohorte->id]), $cabecera)
            ->assertCreated()
            ->assertJsonPath('matricula.estado', 'pendiente')
            ->assertJsonPath('matricula.origen', 'crm');

        $alumno = User::where('id_externo_crm', 'CRM-100')->firstOrFail();
        $this->assertSame(Rol::Estudiante, $alumno->rol);
        $this->assertSame(1, $alumno->matriculas()->count());
        $this->assertSame(1, $alumno->matriculas()->first()->inscripciones()->count());
    }

    public function test_recibir_el_mismo_alumno_dos_veces_no_duplica(): void
    {
        [, $cabecera] = $this->cliente();
        $cohorte = $this->cohorte();
        $datos = $this->convertido(['cohorte_id' => $cohorte->id]);

        $this->postJson('/api/v1/estudiantes-convertidos', $datos, $cabecera)->assertCreated();
        $this->postJson('/api/v1/estudiantes-convertidos', $datos, $cabecera)->assertOk()->assertJsonPath('matricula', null);

        $this->assertSame(1, User::where('id_externo_crm', 'CRM-100')->count());
        $this->assertSame(1, Matricula::count());
    }

    public function test_el_convertido_enlaza_con_un_alumno_cargado_a_mano(): void
    {
        [, $cabecera] = $this->cliente();
        $manual = User::factory()->create(['rol' => Rol::Estudiante, 'email' => 'ana@example.test']);

        $this->postJson('/api/v1/estudiantes-convertidos', $this->convertido(), $cabecera)->assertOk();

        $this->assertSame('CRM-100', $manual->fresh()->id_externo_crm);
        $this->assertSame(1, User::where('email', 'ana@example.test')->count());
    }

    public function test_el_convertido_valida_los_datos(): void
    {
        [, $cabecera] = $this->cliente();

        $this->postJson('/api/v1/estudiantes-convertidos', ['nombres' => 'Ana'], $cabecera)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id_externo_crm', 'apellidos', 'email']);
    }

    public function test_consultar_el_estado_de_matriculas_por_api(): void
    {
        [, $cabecera] = $this->cliente();
        $cohorte = $this->cohorte();
        $this->postJson('/api/v1/estudiantes-convertidos', $this->convertido(['cohorte_id' => $cohorte->id]), $cabecera);
        $matricula = Matricula::firstOrFail();

        $this->getJson('/api/v1/matriculas?id_externo_crm=CRM-100', $cabecera)
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.estado', 'pendiente');
        $this->getJson('/api/v1/matriculas?id_externo_crm=OTRO', $cabecera)->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/matriculas/'.$matricula->id, $cabecera)->assertOk()->assertJsonPath('data.programa', 'Diplomado');
        $this->getJson('/api/v1/matriculas?estado=invalido', $cabecera)->assertUnprocessable();
    }

    public function test_el_admin_genera_y_revoca_el_token_del_cliente(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Administrador]);

        $this->actingAs($admin)->post(route('clientes-api.store'), ['nombre' => 'CRM'])
            ->assertRedirect()->assertSessionHas('token');
        $cliente = ClienteApi::firstOrFail();
        $this->assertSame(1, $cliente->tokens()->count());

        $this->actingAs($admin)->delete(route('clientes-api.destroy', $cliente))->assertRedirect();
        $this->assertSame(0, ClienteApi::count());
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_una_matricula_retirada_no_cambia_de_estado(): void
    {
        $admin = User::factory()->create(['rol' => Rol::Administrador]);
        $alumno = User::factory()->create(['rol' => Rol::Estudiante]);
        $matricula = Matricula::create(['estudiante_id' => $alumno->id, 'estado' => EstadoMatricula::Activo, 'origen' => OrigenMatricula::Manual, 'fecha_matricula' => today()]);

        $this->actingAs($admin)->patch(route('matriculas.estado', $matricula), ['estado' => 'retirado'])->assertRedirect();
        $this->assertNotNull($matricula->fresh()->fecha_retiro);

        $this->actingAs($admin)->patch(route('matriculas.estado', $matricula), ['estado' => 'activo'])->assertSessionHasErrors('estado');
        $this->assertSame(EstadoMatricula::Retirado, $matricula->fresh()->estado);
    }

    public function test_el_estudiante_sube_el_comprobante_y_el_admin_lo_aprueba(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['rol' => Rol::Administrador]);
        $alumno = User::factory()->create(['rol' => Rol::Estudiante]);
        $matricula = Matricula::create(['estudiante_id' => $alumno->id, 'estado' => EstadoMatricula::Pendiente, 'origen' => OrigenMatricula::Manual, 'fecha_matricula' => today()]);

        $this->actingAs($alumno)->post(route('comprobantes.store', $matricula), ['archivo' => UploadedFile::fake()->image('pago.png')])
            ->assertSessionHasNoErrors();
        $comprobante = $matricula->comprobantes()->firstOrFail();
        $this->assertSame(EstadoComprobante::Pendiente, $comprobante->estado);

        $this->actingAs($alumno)->get(route('comprobantes.ver', $comprobante))->assertOk();
        $this->actingAs($alumno)->patch(route('comprobantes.aprobar', $comprobante))->assertForbidden();

        $this->actingAs($admin)->patch(route('comprobantes.aprobar', $comprobante))->assertRedirect();
        $this->assertSame(EstadoComprobante::Aprobado, $comprobante->fresh()->estado);
        $this->assertSame(EstadoMatricula::Activo, $matricula->fresh()->estado);
    }

    public function test_un_estudiante_no_sube_comprobante_a_matricula_ajena_ni_archivo_invalido(): void
    {
        Storage::fake('local');
        $alumno = User::factory()->create(['rol' => Rol::Estudiante]);
        $otro = User::factory()->create(['rol' => Rol::Estudiante]);
        $ajena = Matricula::create(['estudiante_id' => $otro->id, 'estado' => EstadoMatricula::Pendiente, 'fecha_matricula' => today()]);
        $propia = Matricula::create(['estudiante_id' => $alumno->id, 'estado' => EstadoMatricula::Pendiente, 'fecha_matricula' => today()]);

        $this->actingAs($alumno)->post(route('comprobantes.store', $ajena), ['archivo' => UploadedFile::fake()->image('p.png')])->assertForbidden();
        $this->actingAs($alumno)->post(route('comprobantes.store', $propia), ['archivo' => UploadedFile::fake()->create('virus.exe', 10)])
            ->assertSessionHasErrors('archivo');
    }
}
