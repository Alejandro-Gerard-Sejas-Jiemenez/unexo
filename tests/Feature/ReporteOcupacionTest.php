<?php

namespace Tests\Feature;

use App\Enums\EstadoMatricula;
use App\Enums\EstadoPlanificacion;
use App\Models\Grupo;
use App\Models\User;
use App\Support\Reporte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreaDatosDeReportes;
use Tests\TestCase;

class ReporteOcupacionTest extends TestCase
{
    use CreaDatosDeReportes;
    use RefreshDatabase;

    private const SECCION = 'Grupos';

    private const SIN_CUPO = 'Sin cupo definido';

    private const ALCANZA = 'Alcanza el mínimo';

    private const NO_ALCANZA = 'No alcanza el mínimo';

    private function reporte(array $filtros = []): Reporte
    {
        $respuesta = $this->actingAs($this->admin())->get(route('reportes.ocupacion', $filtros));
        $respuesta->assertOk();

        return $respuesta->viewData('reporte');
    }

    /** @param  array<int, EstadoMatricula>  $estados  una matrícula inscrita al grupo por cada estado */
    private function grupoConInscritos(array $estados, array $atributos = []): Grupo
    {
        $grupo = $this->grupo($atributos);
        foreach ($estados as $estado) {
            $this->inscribir($this->matricula(['estado' => $estado]), $grupo);
        }

        return $grupo;
    }

    private function nombres(Reporte $reporte): array
    {
        return $reporte->sinDatos ? [] : array_column($this->filasDe($reporte, self::SECCION), 0);
    }

    private function filaDe(Reporte $reporte, string $nombre): array
    {
        foreach ($this->filasDe($reporte, self::SECCION) as $fila) {
            if ($fila[0] === $nombre) {
                return $fila;
            }
        }
        $this->fail("No aparece el grupo «{$nombre}».");
    }

    // RF-08 / CA-08
    public function test_cada_fila_muestra_sus_datos_el_docente_y_los_inscritos(): void
    {
        $cohorte = $this->cohorte(nombre: 'DEMO Cohorte A');
        $docente = $this->docente(['nombres' => 'Carlos', 'apellidos' => 'Mendoza Rojas']);
        $modulo = $this->modulo(nombre: 'DEMO Python');
        $this->grupoConInscritos([EstadoMatricula::Activo, EstadoMatricula::Pendiente], [
            'nombre' => 'DEMO Grupo 1',
            'modulo_id' => $modulo->id,
            'cohorte' => $cohorte,
            'docente_id' => $docente->id,
            'cupo_minimo' => 5,
        ]);

        $fila = $this->filaDe($this->reporte(), 'DEMO Grupo 1');

        $this->assertSame('DEMO Python', $fila[1]);
        $this->assertSame('DEMO Cohorte A', $fila[2]);
        $this->assertSame('Carlos Mendoza Rojas', $fila[3]);
        // CA-40: Inicio y Fin salen de la edición (2026-03-01 a 2026-08-30)
        $this->assertSame('2026-03-01', $fila[4]->format('Y-m-d'));
        $this->assertSame('2026-08-30', $fila[5]->format('Y-m-d'));
        $this->assertSame('Programado', $fila[6]);
        $this->assertSame(2, $fila[7]);
        $this->assertSame(5, $fila[8]);
    }

    public function test_un_grupo_suelto_muestra_sin_cohorte(): void
    {
        $this->grupo(['nombre' => 'DEMO Suelto']);

        $this->assertSame('Sin cohorte', $this->filaDe($this->reporte(), 'DEMO Suelto')[2]);
    }

    // RF-09 / CA-09
    public function test_indica_si_alcanza_o_no_el_cupo_minimo(): void
    {
        $this->grupoConInscritos(array_fill(0, 7, EstadoMatricula::Activo), ['nombre' => 'DEMO Con 7', 'cupo_minimo' => 10]);
        $this->grupoConInscritos(array_fill(0, 10, EstadoMatricula::Activo), ['nombre' => 'DEMO Con 10', 'cupo_minimo' => 10]);

        $reporte = $this->reporte();

        $this->assertSame(self::NO_ALCANZA, $this->filaDe($reporte, 'DEMO Con 7')[9]);
        $this->assertSame(self::ALCANZA, $this->filaDe($reporte, 'DEMO Con 10')[9]);
    }

    // RF-10 / CA-10
    public function test_un_grupo_sin_cupo_muestra_sin_cupo_definido(): void
    {
        $this->grupo(['nombre' => 'DEMO Nulo', 'cupo_minimo' => null]);
        $this->grupo(['nombre' => 'DEMO Cero', 'cupo_minimo' => 0]);

        $reporte = $this->reporte();

        $this->assertSame(self::SIN_CUPO, $this->filaDe($reporte, 'DEMO Nulo')[9]);
        $this->assertSame(self::SIN_CUPO, $this->filaDe($reporte, 'DEMO Cero')[9]);
    }

    // RF-27 / CA-27 y P-5
    public function test_el_grupo_cancelado_se_lista_sin_indicador_y_no_cuenta_entre_los_que_no_alcanzan(): void
    {
        $this->grupoConInscritos([EstadoMatricula::Activo, EstadoMatricula::Activo], [
            'nombre' => 'DEMO Cancelado', 'cupo_minimo' => 10, 'estado' => EstadoPlanificacion::Cancelado,
        ]);
        $this->grupo(['nombre' => 'DEMO Cancelado sin cupo', 'estado' => EstadoPlanificacion::Cancelado]);

        $reporte = $this->reporte();

        $this->assertNull($this->filaDe($reporte, 'DEMO Cancelado')[9]);
        $this->assertSame(self::SIN_CUPO, $this->filaDe($reporte, 'DEMO Cancelado sin cupo')[9]);
        $this->assertSame(0, $this->totalDe($reporte, 'No alcanzan el cupo mínimo'));
    }

    // RF-28 / CA-28
    public function test_inscritos_cuenta_pendientes_y_activos_y_no_retirados(): void
    {
        $this->grupoConInscritos([
            EstadoMatricula::Pendiente, EstadoMatricula::Pendiente,
            EstadoMatricula::Activo, EstadoMatricula::Activo, EstadoMatricula::Activo,
            EstadoMatricula::Retirado, EstadoMatricula::Retirado, EstadoMatricula::Retirado, EstadoMatricula::Retirado,
        ], ['nombre' => 'DEMO Mixto']);

        $this->assertSame(5, $this->filaDe($this->reporte(), 'DEMO Mixto')[7]);
    }

    // RF-11 / CA-11 y C-06
    public function test_filtra_por_estado_y_sin_filtro_lista_todos(): void
    {
        $this->grupo(['nombre' => 'DEMO Programado']);
        $this->grupo(['nombre' => 'DEMO En curso', 'estado' => EstadoPlanificacion::EnCurso]);
        $this->grupo(['nombre' => 'DEMO Finalizado', 'estado' => EstadoPlanificacion::Finalizado]);
        $this->grupo(['nombre' => 'DEMO Cancelado', 'estado' => EstadoPlanificacion::Cancelado]);

        $this->assertCount(4, $this->nombres($this->reporte()));
        $this->assertSame(['DEMO En curso'], $this->nombres($this->reporte(['estado' => 'en_curso'])));
    }

    // RF-11 / CA-32
    public function test_filtra_por_cohorte(): void
    {
        $cohorteA = $this->cohorte();
        $this->grupo(['nombre' => 'DEMO En A', 'cohorte' => $cohorteA]);
        $this->grupo(['nombre' => 'DEMO En B', 'cohorte' => $this->cohorte()]);
        $this->grupo(['nombre' => 'DEMO Suelto']);

        $this->assertSame(['DEMO En A'], $this->nombres($this->reporte(['cohorte_id' => $cohorteA->id])));
    }

    // RF-11 / CA-33
    public function test_el_solapamiento_de_periodo_incluye_los_extremos(): void
    {
        $this->grupo(['nombre' => 'DEMO Marzo', 'fecha_inicio' => '2026-03-01', 'fecha_fin' => '2026-03-30']);

        $this->assertSame(['DEMO Marzo'], $this->nombres($this->reporte(['desde' => '2026-03-25', 'hasta' => '2026-04-05'])));
        $this->assertSame(['DEMO Marzo'], $this->nombres($this->reporte(['desde' => '2026-03-30'])));
        $this->assertSame([], $this->nombres($this->reporte(['desde' => '2026-03-31'])));
        $this->assertSame(['DEMO Marzo'], $this->nombres($this->reporte(['hasta' => '2026-03-01'])));
        $this->assertSame([], $this->nombres($this->reporte(['hasta' => '2026-02-28'])));
        $this->assertSame(['DEMO Marzo'], $this->nombres($this->reporte(['desde' => '2026-03-10', 'hasta' => '2026-03-12'])));
    }

    // RF-11 / CA-33 / CA-40: el grupo de edición se solapa por el período de su edición
    public function test_el_solapamiento_de_un_grupo_de_edicion_usa_el_periodo_de_la_edicion(): void
    {
        $this->grupo(['nombre' => 'DEMO Edición', 'cohorte' => $this->cohorte()]); // 2026-03-01 a 2026-08-30

        $this->assertSame(['DEMO Edición'], $this->nombres($this->reporte(['desde' => '2026-08-30'])));
        $this->assertSame([], $this->nombres($this->reporte(['desde' => '2026-08-31'])));
        $this->assertSame(['DEMO Edición'], $this->nombres($this->reporte(['hasta' => '2026-03-01'])));
        $this->assertSame([], $this->nombres($this->reporte(['hasta' => '2026-02-28'])));
        $this->assertSame(['DEMO Edición'], $this->nombres($this->reporte(['desde' => '2026-06-01', 'hasta' => '2026-06-02'])));
    }

    // CA-40: la columna Grupo conserva el nombre y el período sale de la edición
    public function test_un_grupo_de_edicion_muestra_su_nombre_automatico_y_el_periodo_de_la_edicion(): void
    {
        $cohorte = $this->cohorte(nombre: 'DEMO Edición 2');
        $this->grupo(['nombre' => 'DEMO Módulo – Edición 2 – Grupo 1', 'cohorte' => $cohorte]);

        $fila = $this->filaDe($this->reporte(), 'DEMO Módulo – Edición 2 – Grupo 1');

        $this->assertSame('DEMO Edición 2', $fila[2]);
        $this->assertSame('01/03/2026', $fila[4]->format('d/m/Y'));
        $this->assertSame('30/08/2026', $fila[5]->format('d/m/Y'));
    }

    // CA-40: sin N+1 al cargar la edición de cada grupo
    public function test_la_cantidad_de_consultas_no_crece_con_los_grupos_de_edicion(): void
    {
        $this->grupo(['cohorte' => $this->cohorte()]);
        $admin = $this->admin();
        $contar = function () use ($admin): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($admin)->get(route('reportes.ocupacion'))->assertOk();

            return count(DB::getQueryLog());
        };
        $con1 = $contar();

        foreach (range(1, 4) as $i) {
            $this->grupo(['cohorte' => $this->cohorte()]);
        }

        $this->assertSame($con1, $contar());
    }

    // RF-12 / CA-12
    public function test_totales_de_grupos_y_de_los_que_no_alcanzan(): void
    {
        $this->grupoConInscritos([EstadoMatricula::Activo], ['cupo_minimo' => 5]);
        $this->grupoConInscritos([], ['cupo_minimo' => 3]);
        $this->grupoConInscritos(array_fill(0, 4, EstadoMatricula::Activo), ['cupo_minimo' => 4]);
        $this->grupoConInscritos([EstadoMatricula::Activo], ['cupo_minimo' => 10, 'estado' => EstadoPlanificacion::Cancelado]);
        $this->grupo();

        $respuesta = $this->actingAs($this->admin())->get(route('reportes.ocupacion'));
        $reporte = $respuesta->viewData('reporte');

        $this->assertSame(5, $this->totalDe($reporte, 'Grupos listados'));
        $this->assertSame(2, $this->totalDe($reporte, 'No alcanzan el cupo mínimo'));
        $this->assertSame('5', $this->numeroDeTarjeta($respuesta->getContent(), 'Grupos listados'));
        $this->assertSame('2', $this->numeroDeTarjeta($respuesta->getContent(), 'No alcanzan el cupo mínimo'));
    }

    // RF-30 / CA-39: solo el número, sin frases
    public function test_las_tarjetas_muestran_solo_el_numero(): void
    {
        $this->grupoConInscritos([], ['cupo_minimo' => 3]);

        $html = $this->actingAs($this->admin())->get(route('reportes.ocupacion'))->getContent();

        $this->assertSame('1', $this->numeroDeTarjeta($html, 'Grupos listados'));
        $this->assertSame('1', $this->numeroDeTarjeta($html, 'No alcanzan el cupo mínimo'));
        $this->assertStringNotContainsString('1 grupo', $html);
        $this->assertStringNotContainsString('1 no alcanza el cupo mínimo', $html);
    }

    // CA-37
    public function test_sin_grupos_muestra_ceros_y_el_aviso(): void
    {
        $respuesta = $this->actingAs($this->admin())->get(route('reportes.ocupacion'));
        $reporte = $respuesta->viewData('reporte');

        $this->assertTrue($reporte->sinDatos);
        $this->assertSame(0, $this->totalDe($reporte, 'Grupos listados'));
        $this->assertSame([], $reporte->secciones);
        $respuesta->assertSee(Reporte::AVISO_SIN_DATOS);
    }

    public function test_los_grupos_se_ordenan_por_inicio_descendente_y_luego_por_nombre(): void
    {
        $this->grupo(['nombre' => 'DEMO B', 'fecha_inicio' => '2026-03-01']);
        $this->grupo(['nombre' => 'DEMO A', 'fecha_inicio' => '2026-03-01']);
        $this->grupo(['nombre' => 'DEMO Nuevo', 'fecha_inicio' => '2026-05-01', 'fecha_fin' => '2026-06-01']);

        $this->assertSame(['DEMO Nuevo', 'DEMO A', 'DEMO B'], $this->nombres($this->reporte()));
    }

    // CA-40: el orden usa el inicio efectivo, también el de la edición
    public function test_el_orden_por_inicio_descendente_incluye_grupos_de_edicion(): void
    {
        $cohorteTardia = $this->cohorte();
        $cohorteTardia->update(['fecha_inicio' => '2026-04-01', 'fecha_fin' => '2026-09-30']);
        $this->grupo(['nombre' => 'DEMO Suelto marzo', 'fecha_inicio' => '2026-03-01']);
        $this->grupo(['nombre' => 'DEMO Suelto mayo', 'fecha_inicio' => '2026-05-01', 'fecha_fin' => '2026-06-01']);
        $this->grupo(['nombre' => 'DEMO Edición abril', 'cohorte' => $cohorteTardia]);

        $this->assertSame(
            ['DEMO Suelto mayo', 'DEMO Edición abril', 'DEMO Suelto marzo'],
            $this->nombres($this->reporte()),
        );
    }

    // RNF-02
    public function test_no_muestra_correo_ni_documento_del_docente_ni_datos_de_estudiantes(): void
    {
        $docente = $this->docente([
            'nombres' => 'Lucía', 'apellidos' => 'Vargas Soliz',
            'email' => 'lucia.secreta@nexo.test', 'documento_identidad' => '7654321', 'telefono' => '70011223',
        ]);
        $estudiante = User::factory()->create(['nombres' => 'Zacarías', 'apellidos' => 'Quispe Mamani', 'email' => 'zacarias@nexo.test']);
        $grupo = $this->grupo(['docente_id' => $docente->id]);
        $this->inscribir($this->matricula(['estudiante_id' => $estudiante->id]), $grupo);

        $this->actingAs($this->admin())->get(route('reportes.ocupacion'))
            ->assertOk()
            ->assertSee('Lucía Vargas Soliz')
            ->assertDontSee('lucia.secreta@nexo.test')
            ->assertDontSee('7654321')
            ->assertDontSee('70011223')
            ->assertDontSee('Zacarías')
            ->assertDontSee('zacarias@nexo.test');
    }

    public function test_los_estados_del_filtro_salen_del_enum_y_los_filtros_se_describen(): void
    {
        $cohorte = $this->cohorte(nombre: 'DEMO Cohorte A');
        $respuesta = $this->actingAs($this->admin())->get(route('reportes.ocupacion', ['estado' => 'cancelado', 'cohorte_id' => $cohorte->id, 'desde' => '2026-03-01']));

        foreach (EstadoPlanificacion::cases() as $estado) {
            $respuesta->assertSee($estado->label());
        }
        $this->assertSame([
            ['etiqueta' => 'Estado del grupo', 'valor' => 'Cancelado'],
            ['etiqueta' => 'Cohorte', 'valor' => 'DEMO Cohorte A'],
            ['etiqueta' => 'Período desde', 'valor' => '01/03/2026'],
            ['etiqueta' => 'Período hasta', 'valor' => 'Todos'],
        ], $respuesta->viewData('reporte')->filtros);
    }
}
