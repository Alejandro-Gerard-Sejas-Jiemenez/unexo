<?php

namespace Tests\Unit;

use App\Enums\Certificado;
use App\Enums\DiaSemana;
use App\Enums\EstadoPlanificacion;
use Tests\TestCase;

class EnumsCatalogoTest extends TestCase
{
    // RF-19
    public function test_dia_semana_va_de_lunes_1_a_domingo_7_con_etiquetas_en_espanol(): void
    {
        $this->assertCount(7, DiaSemana::cases());
        $this->assertSame(1, DiaSemana::Lunes->value);
        $this->assertSame(7, DiaSemana::Domingo->value);
        $this->assertSame(
            ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'],
            array_map(fn (DiaSemana $dia) => $dia->label(), DiaSemana::cases()),
        );
    }

    public function test_dia_semana_en_mensaje_va_en_minuscula(): void
    {
        $this->assertSame('miércoles', DiaSemana::Miercoles->enMensaje());
        $this->assertSame('sábado', DiaSemana::Sabado->enMensaje());
    }

    // RF-05
    public function test_certificado_tiene_valores_y_etiquetas(): void
    {
        $this->assertSame('certificado', Certificado::Certificado->value);
        $this->assertSame('sin_certificado', Certificado::SinCertificado->value);
        $this->assertSame('Certificado', Certificado::Certificado->label());
        $this->assertSame('Sin certificado', Certificado::SinCertificado->label());
    }

    // RF-26
    public function test_estados_visibles_para_crm_son_programado_y_en_curso(): void
    {
        $this->assertSame(
            [EstadoPlanificacion::Programado, EstadoPlanificacion::EnCurso],
            EstadoPlanificacion::visiblesParaCrm(),
        );
    }

    // RF-02
    public function test_limites_nuevos_del_catalogo(): void
    {
        $this->assertSame(500, config('limites.modulos.prerrequisito'));
        $this->assertSame(14, config('limites.grupos.horarios_max'));
        $this->assertSame(2048, config('limites.programas.portada_kb'));
    }
}
