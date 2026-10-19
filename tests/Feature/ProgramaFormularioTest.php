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
        return $this->actingAs($this->admin())->put(route('programas.update', $programa), $this->datos($extra));
    }

    public function test_el_formulario_y_las_pantallas_no_muestran_nivel_ni_perfil(): void
    {
        // CA-04
        $programa = $this->programa();
        $admin = $this->admin();

        foreach ([route('programas.create'), route('programas.edit', $programa), route('programas.index'), route('programas.show', $programa)] as $url) {
            $this->actingAs($admin)->get($url)
                ->assertOk()
                ->assertDontSee('Nivel Académico', false)
                ->assertDontSee('nivel_academico', false)
                ->assertDontSee('Perfil de Egreso', false)
                ->assertDontSee('perfil_egreso', false);
        }
    }

    public function test_los_datos_de_nivel_y_perfil_enviados_se_ignoran(): void
    {
        // CA-04
        $programa = $this->programa();

        $this->actualizar($programa, ['nivel_academico' => 'Postgrado', 'perfil_egreso' => 'x'])->assertRedirect();

        $this->assertArrayNotHasKey('nivel_academico', $programa->fresh()->getAttributes());
    }

    public function test_certificado_solo_ofrece_la_lista_o_vacio(): void
    {
        // CA-05
        $this->actingAs($this->admin())->get(route('programas.create'))
            ->assertOk()
            ->assertSee('— No definido —', false)
            ->assertSee('value="certificado"', false)
            ->assertSee('value="sin_certificado"', false)
            ->assertSee('Sin certificado')
            ->assertDontSee('<input type="text" id="certificado_otorga"', false);
    }

    public function test_un_certificado_fuera_de_la_lista_se_rechaza(): void
    {
        // CA-05
        $programa = $this->programa();

        $this->actualizar($programa, ['certificado_otorga' => 'Diploma de honor'])
            ->assertSessionHasErrors('certificado_otorga');

        $this->assertNull($programa->fresh()->certificado_otorga);
    }

    public function test_un_certificado_de_la_lista_o_vacio_se_guarda(): void
    {
        // CA-05
        $programa = $this->programa();

        $this->actualizar($programa, ['certificado_otorga' => 'sin_certificado'])->assertSessionDoesntHaveErrors();
        $this->assertSame(Certificado::SinCertificado, $programa->fresh()->certificado_otorga);

        $this->actualizar($programa, ['certificado_otorga' => ''])->assertSessionDoesntHaveErrors();
        $this->assertNull($programa->fresh()->certificado_otorga);
    }

    public function test_un_certificado_antiguo_en_texto_libre_se_ve_como_no_definido(): void
    {
        // CA-50 (parte formulario)
        $programa = $this->programa();
        $programa->forceFill(['certificado_otorga' => 'Diploma de Especialista'])->save();

        $html = $this->actingAs($this->admin())->get(route('programas.edit', $programa))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<option value=""\s+selected>\s*— No definido —/u', $html);
        $this->assertStringNotContainsString('Diploma de Especialista', $html);
    }

    public function test_el_formulario_pinta_la_zona_de_portada(): void
    {
        // CA-06
        $this->actingAs($this->admin())->get(route('programas.create'))
            ->assertOk()
            ->assertSee('data-portada', false)
            ->assertSee('accept="image/jpeg,image/png,image/webp"', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('Arrastra una imagen aquí');
    }

    public function test_una_imagen_valida_se_guarda_como_portada(): void
    {
        // CA-07
        Storage::fake('public');
        $programa = $this->programa();

        $this->actualizar($programa, ['imagen_portada_url' => UploadedFile::fake()->create('portada.jpg', 100, 'image/jpeg')])
            ->assertSessionDoesntHaveErrors();

        $ruta = $programa->fresh()->imagen_portada_url;
        $this->assertStringStartsWith('/storage/programas/', $ruta);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $ruta));
    }

    public function test_al_crear_una_imagen_valida_se_guarda_como_portada(): void
    {
        // CA-07
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post(route('programas.store'), $this->datos(['imagen_portada_url' => UploadedFile::fake()->create('portada.png', 100, 'image/png')]))
            ->assertSessionDoesntHaveErrors();

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

    /** @dataProvider archivosInvalidos */
    public function test_un_archivo_invalido_se_rechaza_y_conserva_la_portada(string $nombre, int $kb, string $mime): void
    {
        // CA-08
        Storage::fake('public');
        $programa = $this->programa(['imagen_portada_url' => '/storage/programas/anterior.jpg']);

        $this->actualizar($programa, ['nombre' => 'DEMO-Otro', 'imagen_portada_url' => UploadedFile::fake()->create($nombre, $kb, $mime)])
            ->assertSessionHasErrors(['imagen_portada_url' => self::MENSAJE_PORTADA]);

        $programa->refresh();
        $this->assertSame('/storage/programas/anterior.jpg', $programa->imagen_portada_url);
        $this->assertSame('DEMO-Diplomado', $programa->nombre);
    }

    public function test_dos_megabytes_exactos_se_aceptan(): void
    {
        // CA-08 (borde)
        Storage::fake('public');
        $programa = $this->programa();

        $this->actualizar($programa, ['imagen_portada_url' => UploadedFile::fake()->create('limite.webp', 2048, 'image/webp')])
            ->assertSessionDoesntHaveErrors();

        $this->assertNotNull($programa->fresh()->imagen_portada_url);
    }

    public function test_guardar_sin_imagen_conserva_la_portada(): void
    {
        // CA-09
        $programa = $this->programa(['imagen_portada_url' => '/storage/programas/anterior.jpg']);

        $this->actualizar($programa, ['nombre' => 'DEMO-Renombrado'])->assertSessionDoesntHaveErrors();

        $programa->refresh();
        $this->assertSame('/storage/programas/anterior.jpg', $programa->imagen_portada_url);
        $this->assertSame('DEMO-Renombrado', $programa->nombre);
    }

    public function test_el_precio_base_se_edita_y_no_hay_descuento(): void
    {
        // CA-10
        $programa = $this->programa();

        $this->actingAs($this->admin())->get(route('programas.edit', $programa))
            ->assertOk()
            ->assertSee('Precio base')
            ->assertDontSee('descuento', false)
            ->assertDontSee('Descuento', false);

        $this->actualizar($programa, ['precio' => '1800.50'])->assertSessionDoesntHaveErrors();

        $this->assertEquals(1800.50, $programa->fresh()->precio);
        $this->assertArrayNotHasKey('descuento', $programa->fresh()->getAttributes());
    }

    public function test_se_conserva_la_unicidad_de_codigo_y_de_nombre_con_version(): void
    {
        // CA-04 (reglas existentes intactas)
        $this->programa();

        $this->actingAs($this->admin())
            ->post(route('programas.store'), $this->datos())
            ->assertSessionHasErrors(['codigo', 'nombre']);
    }
}
