<?php

namespace Tests\Feature;

use App\Enums\EstadoComprobante;
use App\Enums\EstadoMatricula;
use App\Enums\Rol;
use App\Models\Comprobante;
use App\Models\Matricula;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ComprobantePanelApiTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['rol' => Rol::Administrador, 'estado' => 'activo']);
    }

    private function estudiante(): User
    {
        return User::factory()->create(['rol' => Rol::Estudiante, 'estado' => 'activo']);
    }

    private function matriculaPendiente(User $estudiante): Matricula
    {
        return Matricula::create([
            'estudiante_id' => $estudiante->id,
            'estado' => EstadoMatricula::Pendiente,
            'fecha_matricula' => today(),
        ]);
    }

    public function test_el_estudiante_sube_un_comprobante_y_puede_verlo(): void
    {
        Storage::fake('local');
        $estudiante = $this->estudiante();
        $matricula = $this->matriculaPendiente($estudiante);

        $respuesta = $this->actingAs($estudiante)
            ->post('/api/panel/matriculas/'.$matricula->id.'/comprobantes', [
                'archivo' => UploadedFile::fake()->create('baucher.pdf', 120, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('comprobante.estado', 'pendiente');

        $comprobanteId = $respuesta->json('comprobante.id');
        $comprobante = Comprobante::findOrFail($comprobanteId);
        Storage::disk('local')->assertExists($comprobante->archivo_ruta);

        $this->actingAs($estudiante)
            ->get("/api/panel/comprobantes/{$comprobante->id}")
            ->assertOk();
    }

    public function test_otro_estudiante_no_puede_subir_ni_ver_un_comprobante_ajeno(): void
    {
        Storage::fake('local');
        $duenio = $this->estudiante();
        $otro = $this->estudiante();
        $matricula = $this->matriculaPendiente($duenio);

        $comprobante = $matricula->comprobantes()->create([
            'archivo_ruta' => UploadedFile::fake()->image('pago.png')->store('comprobantes'),
            'estado' => EstadoComprobante::Pendiente,
            'subido_por' => $duenio->id,
        ]);

        $this->actingAs($otro)
            ->post('/api/panel/matriculas/'.$matricula->id.'/comprobantes', [
                'archivo' => UploadedFile::fake()->image('otro.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertForbidden();

        $this->actingAs($otro)
            ->get("/api/panel/comprobantes/{$comprobante->id}")
            ->assertForbidden();
    }

    public function test_el_admin_aprueba_un_comprobante_y_activa_la_matricula(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $estudiante = $this->estudiante();
        $matricula = $this->matriculaPendiente($estudiante);

        $comprobante = $matricula->comprobantes()->create([
            'archivo_ruta' => UploadedFile::fake()->image('comprobante.png')->store('comprobantes'),
            'estado' => EstadoComprobante::Pendiente,
            'subido_por' => $estudiante->id,
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/panel/comprobantes/{$comprobante->id}/aprobar")
            ->assertOk();

        $this->assertSame(EstadoComprobante::Aprobado, $comprobante->fresh()->estado);
        $this->assertSame(EstadoMatricula::Activo, $matricula->fresh()->estado);
    }

    public function test_el_admin_rechaza_un_comprobante_sin_activar_la_matricula(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $estudiante = $this->estudiante();
        $matricula = $this->matriculaPendiente($estudiante);

        $comprobante = $matricula->comprobantes()->create([
            'archivo_ruta' => UploadedFile::fake()->image('borroso.jpg')->store('comprobantes'),
            'estado' => EstadoComprobante::Pendiente,
            'subido_por' => $estudiante->id,
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/panel/comprobantes/{$comprobante->id}/rechazar")
            ->assertOk();

        $this->assertSame(EstadoComprobante::Rechazado, $comprobante->fresh()->estado);
        $this->assertSame(EstadoMatricula::Pendiente, $matricula->fresh()->estado);
    }
}
