<?php

namespace Tests\Feature;

use App\Enums\Certificado;
use App\Enums\EstadoComercial;
use App\Enums\Rol;
use App\Models\Programa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProgramaFormularioTest extends TestCase
{
    use RefreshDatabase;

    private const MENSAJE_PORTADA = 'La portada debe ser una imagen JPG, PNG o WebP de hasta 2 MB.';

    private function admin(): User
    {
        return User::factory()->create(['rol' => Rol::Administrador]);
    }

    private function programa(array $extra = []): Programa
    {
        return Programa::create(array_merge([
            'codigo' => 'DEMO-P1',
            'nombre' => 'DEMO-Diplomado',
            'version' => '1.0',
            'estado_comercial' => EstadoComercial::Borrador,
            'precio' => 1500,
        ], $extra));
    }

    private function datos(array $extra = []): array
    {
        return array_merge([
            'codigo' => 'DEMO-P1',
            'nombre' => 'DEMO-Diplomado',
            'version' => '1.0',
            'estado_comercial' => EstadoComercial::Borrador->value,
        ], $extra);
    }

    private function actualizar(Programa $programa, array $extra = [])
    {
        return $this->actingAs($this->admin())
            ->post("/api/panel/catalogo/programas/{$programa->id}", array_merge($this->datos($extra), ['_method' => 'PUT']), ['Accept' => 'application/json']);
    }

    public function test_la_api_de_programas_no_incluye_nivel_ni_perfil(): void
    {
        $programa = $this->programa();
        $admin = $this->admin();

        foreach (['/api/panel/catalogo/programas', "/api/panel/catalogo/programas/{$programa->id}"] as $url) {
            $json = $this->actingAs($admin)->getJson($url)->assertOk()->getContent();
            $this->assertStringNotContainsString('"nivel_academico"', $json);
            $this->assertStringNotContainsString('"perfil_egreso"', $json);
        }
    }

    public function test_los_datos_de_nivel_y_perfil_enviados_se_ignoran(): void
    {
        $programa = $this->programa();

        $this->actualizar($programa, ['nivel_academico' => 'Postgrado', 'perfil_egreso' => 'x'])->assertOk();

        $this->assertArrayNotHasKey('nivel_academico', $programa->fresh()->getAttributes());
    }

    public function test_las_opciones_de_certificado_solo_ofrecen_valores_validos(): void
    {
        $res = $this->actingAs($this->admin())->getJson('/api/panel/catalogo/opciones')->assertOk();
        $valores = collect($res->json('certificados'))->pluck('valor')->all();

        $this->assertSame(['certificado', 'sin_certificado'], $valores);
    }

    public function test_un_certificado_fuera_de_la_lista_se_rechaza(): void
    {
        $programa = $this->programa();

        $this->actualizar($programa, ['certificado_otorga' => 'Diploma de honor'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('certificado_otorga');

        $this->assertNull($programa->fresh()->certificado_otorga);
    }

    public function test_un_certificado_de_la_lista_o_vacio_se_guarda(): void
    {
        $programa = $this->programa();

        $this->actualizar($programa, ['certificado_otorga' => 'sin_certificado'])->assertOk();
        $this->assertSame(Certificado::SinCertificado, $programa->fresh()->certificado_otorga);

        $this->actualizar($programa, ['certificado_otorga' => ''])->assertOk();
        $this->assertNull($programa->fresh()->certificado_otorga);
    }

    public function test_un_certificado_antiguo_en_texto_libre_se_devuelve_nulo_en_resource(): void
    {
        $programa = $this->programa();
        $programa->forceFill(['certificado_otorga' => 'Diploma de Especialista'])->save();

        $res = $this->actingAs($this->admin())->getJson("/api/panel/catalogo/programas/{$programa->id}")->assertOk();
        $this->assertNull($res->json('data.certificado_otorga'));
    }

    public function test_una_imagen_valida_se_guarda_como_portada(): void
    {
        Storage::fake('public');
        $programa = $this->programa();

        $this->actualizar($programa, ['imagen_portada_url' => UploadedFile::fake()->create('portada.jpg', 100, 'image/jpeg')])
            ->assertOk();

        $ruta = $programa->fresh()->imagen_portada_url;
        $this->assertStringStartsWith('/storage/programas/', $ruta);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $ruta));
    }

    public function test_al_crear_una_imagen_valida_se_guarda_como_portada(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post('/api/panel/catalogo/programas', $this->datos(['imagen_portada_url' => UploadedFile::fake()->create('portada.png', 100, 'image/png')]), ['Accept' => 'application/json'])
            ->assertCreated();

        $this->assertStringStartsWith('/storage/programas/', Programa::firstOrFail()->imagen_portada_url);
    }

    public static function archivosInvalidos(): array
    {
        return [
            'PDF' => ['documento.pdf', 100, 'application/pdf'],
            'más de 2 MB' => ['grande.jpg', 3072, 'image/jpeg'],
            'GIF' => ['animada.gif', 100, 'image/gif'],
            'SVG' => ['vector.svg', 10, 'image/svg+xml'],
        ];
    }

    #[DataProvider('archivosInvalidos')]
    public function test_un_archivo_invalido_se_rechaza_y_conserva_la_portada(string $nombre, int $kb, string $mime): void
    {
        Storage::fake('public');
        $programa = $this->programa(['imagen_portada_url' => '/storage/programas/anterior.jpg']);

        $this->actualizar($programa, ['nombre' => 'DEMO-Otro', 'imagen_portada_url' => UploadedFile::fake()->create($nombre, $kb, $mime)])
            ->assertStatus(422)
            ->assertJsonPath('errors.imagen_portada_url.0', self::MENSAJE_PORTADA);

        $programa->refresh();
        $this->assertSame('/storage/programas/anterior.jpg', $programa->imagen_portada_url);
        $this->assertSame('DEMO-Diplomado', $programa->nombre);
    }

    public function test_dos_megabytes_exactos_se_aceptan(): void
    {
        Storage::fake('public');
        $programa = $this->programa();

        $this->actualizar($programa, ['imagen_portada_url' => UploadedFile::fake()->create('limite.webp', 2048, 'image/webp')])
            ->assertOk();

        $this->assertNotNull($programa->fresh()->imagen_portada_url);
    }

    public function test_guardar_sin_imagen_conserva_la_portada(): void
    {
        $programa = $this->programa(['imagen_portada_url' => '/storage/programas/anterior.jpg']);

        $this->actualizar($programa, ['nombre' => 'DEMO-Renombrado'])->assertOk();

        $programa->refresh();
        $this->assertSame('/storage/programas/anterior.jpg', $programa->imagen_portada_url);
        $this->assertSame('DEMO-Renombrado', $programa->nombre);
    }

    public function test_el_precio_base_se_edita_y_no_hay_descuento(): void
    {
        $programa = $this->programa();

        $this->actualizar($programa, ['precio' => '1800.50'])->assertOk();

        $this->assertEquals(1800.50, $programa->fresh()->precio);
        $this->assertArrayNotHasKey('descuento', $programa->fresh()->getAttributes());
    }

    public function test_se_conserva_la_unicidad_de_codigo_y_de_nombre_con_version(): void
    {
        $this->programa();

        $this->actingAs($this->admin())
            ->postJson('/api/panel/catalogo/programas', $this->datos())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['codigo', 'nombre']);
    }
}
