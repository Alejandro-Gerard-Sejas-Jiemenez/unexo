<?php

namespace Tests\Concerns;

use App\Enums\EstadoPlanificacion;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\HorarioGrupo;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\User;

trait CreaGruposConHorarios
{
    private int $secuenciaGrupos = 0;

    /**
     * Crea un grupo con sus horarios. Con $cohorte es de edición y no guarda fechas propias;
     * sin $cohorte es suelto y usa 2026-03-01 a 2026-03-30 salvo que $atributos las indique.
     *
     * @param  array<int, array{0: int, 1: string, 2: string}>  $horarios  [día 1-7, hora inicio, hora fin]
     * @param  array<string, mixed>  $atributos  Sobrescribe columnas de grupos (p. ej. fecha_inicio, estado, numero)
     */
    protected function crearGrupoConHorarios(array $horarios = [], ?Cohorte $cohorte = null, array $atributos = []): Grupo
    {
        $n = ++$this->secuenciaGrupos;

        $base = [
            'modulo_id' => Modulo::create(['codigo' => "DEMO-GH$n", 'nombre' => "DEMO Módulo GH$n"])->id,
            'docente_id' => User::factory()->create()->id,
            'nombre' => "DEMO Grupo $n",
            'estado' => EstadoPlanificacion::Programado,
        ];

        if ($cohorte) {
            $base['cohorte_id'] = $cohorte->id;
        } else {
            $base += ['fecha_inicio' => '2026-03-01', 'fecha_fin' => '2026-03-30'];
        }

        $grupo = Grupo::create($atributos + $base);

        foreach ($horarios as [$dia, $horaInicio, $horaFin]) {
            HorarioGrupo::create([
                'grupo_id' => $grupo->id,
                'dia_semana' => $dia,
                'hora_inicio' => $horaInicio,
                'hora_fin' => $horaFin,
            ]);
        }

        return $grupo;
    }

    /** Crea una edición (cohorte) de un programa nuevo con el período indicado. */
    protected function crearEdicionConPeriodo(string $inicio, string $fin, array $atributos = []): Cohorte
    {
        $n = ++$this->secuenciaGrupos;
        $programa = Programa::create(['codigo' => "DEMO-GP$n", 'nombre' => "DEMO Programa GP$n", 'version' => '1.0']);

        return Cohorte::create($atributos + [
            'programa_id' => $programa->id,
            'nombre' => "DEMO Edición GP$n",
            'fecha_inicio' => $inicio,
            'fecha_fin' => $fin,
            'estado' => EstadoPlanificacion::Programado,
        ]);
    }
}
