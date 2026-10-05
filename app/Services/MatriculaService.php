<?php

namespace App\Services;

use App\Enums\EstadoMatricula;
use App\Enums\OrigenMatricula;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\InscripcionGrupo;
use App\Models\Matricula;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MatriculaService
{
    /** Inscribe a un estudiante en la cohorte completa: una fila por cada grupo de la cohorte. */
    public function inscribirEnCohorte(User $estudiante, Cohorte $cohorte): Matricula
    {
        return DB::transaction(function () use ($estudiante, $cohorte) {
            $matricula = Matricula::create([
                'estudiante_id' => $estudiante->id,
                'cohorte_id' => $cohorte->id,
                'estado' => EstadoMatricula::Pendiente,
                'origen' => OrigenMatricula::Manual,
                'fecha_matricula' => today(),
            ]);

            foreach ($cohorte->grupos as $grupo) {
                InscripcionGrupo::create(['matricula_id' => $matricula->id, 'grupo_id' => $grupo->id]);
            }

            return $matricula;
        });
    }

    /** Inscribe a un estudiante en un grupo de módulo suelto (sin cohorte). */
    public function inscribirEnGrupoSuelto(User $estudiante, Grupo $grupo): Matricula
    {
        return DB::transaction(function () use ($estudiante, $grupo) {
            $matricula = Matricula::create([
                'estudiante_id' => $estudiante->id,
                'cohorte_id' => null,
                'estado' => EstadoMatricula::Pendiente,
                'origen' => OrigenMatricula::Manual,
                'fecha_matricula' => today(),
            ]);

            InscripcionGrupo::create(['matricula_id' => $matricula->id, 'grupo_id' => $grupo->id]);

            return $matricula;
        });
    }

    public function yaInscritoEnCohorte(User $estudiante, Cohorte $cohorte): bool
    {
        return Matricula::where('estudiante_id', $estudiante->id)
            ->where('cohorte_id', $cohorte->id)
            ->exists();
    }

    public function yaInscritoEnGrupo(User $estudiante, Grupo $grupo): bool
    {
        return InscripcionGrupo::where('grupo_id', $grupo->id)
            ->whereHas('matricula', fn ($m) => $m->where('estudiante_id', $estudiante->id))
            ->exists();
    }
}
