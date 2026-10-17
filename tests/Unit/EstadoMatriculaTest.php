<?php

namespace Tests\Unit;

use App\Enums\EstadoMatricula;
use PHPUnit\Framework\TestCase;

class EstadoMatriculaTest extends TestCase
{
    // RF-28 / CA-28
    public function test_cuentan_como_inscritos_las_matriculas_pendientes_y_activas(): void
    {
        $this->assertSame(
            [EstadoMatricula::Pendiente, EstadoMatricula::Activo],
            EstadoMatricula::queCuentanComoInscritos(),
        );
    }

    // RF-28 / CA-28
    public function test_las_matriculas_retiradas_no_cuentan_como_inscritas(): void
    {
        $this->assertNotContains(EstadoMatricula::Retirado, EstadoMatricula::queCuentanComoInscritos());
    }
}
