<?php

namespace Database\Seeders;

use App\Enums\EstadoMatricula;
use App\Enums\EstadoPlanificacion;
use App\Enums\Modalidad;
use App\Enums\OrigenMatricula;
use App\Models\Aula;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\InscripcionGrupo;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Datos inventados (prefijo DEMO) de cohortes, grupos y matrículas para desarrollo y presentación.
 */
class PlanificacionDemoSeeder extends Seeder
{
    public function run(): void
    {
        $carlos = User::where('email', 'carlos.mendoza@nexo.test')->firstOrFail();
        $lucia = User::where('email', 'lucia.salvatierra@nexo.test')->firstOrFail();
        $andres = User::where('email', 'andres.flores@nexo.test')->firstOrFail();
        $daniela = User::where('email', 'daniela.choque@nexo.test')->firstOrFail();
        $mateo = User::where('email', 'mateo.justiniano@nexo.test')->firstOrFail();

        $programa = Programa::where('codigo', 'DEMO-P01')->firstOrFail();
        $cohorte = Cohorte::firstOrCreate(
            ['programa_id' => $programa->id, 'nombre' => 'DEMO Cohorte 2026-2'],
            ['fecha_inicio' => '2026-09-21', 'fecha_fin' => '2026-12-18', 'estado' => EstadoPlanificacion::EnCurso],
        );

        $tramos = [
            'DEMO-M01' => ['2026-09-21', '2026-10-09', $carlos],
            'DEMO-M02' => ['2026-10-12', '2026-11-06', $carlos],
            'DEMO-M03' => ['2026-11-09', '2026-12-04', $lucia],
            'DEMO-M04' => ['2026-12-07', '2026-12-18', $lucia],
        ];

        $gruposCohorte = [];
        foreach ($tramos as $codigo => [$inicio, $fin, $docente]) {
            $modulo = Modulo::where('codigo', $codigo)->firstOrFail();
            $gruposCohorte[] = Grupo::firstOrCreate(
                ['modulo_id' => $modulo->id, 'cohorte_id' => $cohorte->id],
                [
                    'docente_id' => $docente->id,
                    'nombre' => 'Grupo A',
                    'fecha_inicio' => $inicio,
                    'fecha_fin' => $fin,
                    'modalidad' => Modalidad::Virtual,
                    'enlace_videoconferencia' => 'https://meet.example.test/demo-'.strtolower($codigo),
                    'cupo_minimo' => 20,
                    'estado' => EstadoPlanificacion::Programado,
                ],
            );
        }

        $aula = Aula::firstOrCreate(['nombre' => 'DEMO Aula 101'], ['capacidad' => 30]);
        $grupoSuelto = Grupo::firstOrCreate(
            ['modulo_id' => Modulo::where('codigo', 'DEMO-M04')->firstOrFail()->id, 'cohorte_id' => null],
            [
                'docente_id' => $lucia->id,
                'aula_id' => $aula->id,
                'nombre' => 'Grupo B (módulo suelto)',
                'fecha_inicio' => '2026-09-28',
                'fecha_fin' => '2026-10-30',
                'modalidad' => Modalidad::Presencial,
                'cupo_minimo' => 20,
                'estado' => EstadoPlanificacion::Programado,
            ],
        );

        $this->matricular($andres, $cohorte, EstadoMatricula::Activo, $gruposCohorte);
        $this->matricular($daniela, $cohorte, EstadoMatricula::Pendiente, $gruposCohorte);
        $this->matricular($mateo, null, EstadoMatricula::Activo, [$grupoSuelto]);
    }

    /** @param  array<int, Grupo>  $grupos */
    private function matricular(User $estudiante, ?Cohorte $cohorte, EstadoMatricula $estado, array $grupos): void
    {
        $matricula = Matricula::firstOrCreate(
            ['estudiante_id' => $estudiante->id, 'cohorte_id' => $cohorte?->id],
            ['estado' => $estado, 'origen' => OrigenMatricula::Manual, 'fecha_matricula' => '2026-09-14'],
        );

        foreach ($grupos as $grupo) {
            InscripcionGrupo::firstOrCreate(['matricula_id' => $matricula->id, 'grupo_id' => $grupo->id]);
        }
    }
}
