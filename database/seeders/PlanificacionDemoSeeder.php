<?php

namespace Database\Seeders;

use App\Enums\DiaSemana;
use App\Enums\EstadoMatricula;
use App\Enums\EstadoPlanificacion;
use App\Enums\Modalidad;
use App\Enums\OrigenMatricula;
use App\Models\Aula;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\HorarioGrupo;
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

        $docentesPorModulo = [
            'DEMO-M01' => $carlos,
            'DEMO-M02' => $carlos,
            'DEMO-M03' => $lucia,
            'DEMO-M04' => $lucia,
        ];

        $gruposCohorte = [];
        foreach ($docentesPorModulo as $codigo => $docente) {
            $modulo = Modulo::where('codigo', $codigo)->firstOrFail();
            $grupo = Grupo::firstOrCreate(
                ['modulo_id' => $modulo->id, 'cohorte_id' => $cohorte->id],
                [
                    'docente_id' => $docente->id,
                    'nombre' => 'Grupo 1',
                    'numero' => 1,
                    'modalidad' => Modalidad::Virtual,
                    'enlace_videoconferencia' => 'https://meet.example.test/demo-'.strtolower($codigo),
                    'cupo_minimo' => 20,
                    'estado' => EstadoPlanificacion::Programado,
                ],
            );
            $this->asignarHorarios($grupo);
            $gruposCohorte[] = $grupo;
        }

        $aula = Aula::firstOrCreate(['nombre' => 'DEMO Aula 101'], ['capacidad' => 30]);
        $grupoSuelto = Grupo::firstOrCreate(
            ['modulo_id' => Modulo::where('codigo', 'DEMO-M04')->firstOrFail()->id, 'cohorte_id' => null],
            [
                'docente_id' => $lucia->id,
                'aula_id' => $aula->id,
                'nombre' => 'Grupo 1',
                'numero' => 1,
                'fecha_inicio' => '2026-09-28',
                'fecha_fin' => '2026-10-30',
                'modalidad' => Modalidad::Presencial,
                'cupo_minimo' => 20,
                'estado' => EstadoPlanificacion::Programado,
            ],
        );

        $this->asignarHorarios($grupoSuelto);

        $this->matricular($andres, $cohorte, EstadoMatricula::Activo, $gruposCohorte);
        $this->matricular($daniela, $cohorte, EstadoMatricula::Pendiente, $gruposCohorte);
        $this->matricular($mateo, null, EstadoMatricula::Activo, [$grupoSuelto]);
    }

    private function asignarHorarios(Grupo $grupo): void
    {
        foreach ([DiaSemana::Lunes, DiaSemana::Miercoles] as $dia) {
            HorarioGrupo::firstOrCreate(
                ['grupo_id' => $grupo->id, 'dia_semana' => $dia],
                ['hora_inicio' => '19:00', 'hora_fin' => '21:00'],
            );
        }
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
