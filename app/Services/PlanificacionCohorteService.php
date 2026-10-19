<?php

namespace App\Services;

use App\Enums\EstadoPlanificacion;
use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Modulo;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlanificacionCohorteService
{
    /**
     * Apertura asistida de los grupos para todos los módulos del programa de la cohorte.
     * Mantiene el orden oficial del programa; los grupos toman el período de la edición y se crean sin horarios.
     *
     * @return Collection<int, Grupo>
     */
    public function generarGruposParaCohorte(
        Cohorte $cohorte,
        ?User $docente = null,
        ?int $cupoMinimo = null,
        Modalidad $modalidad = Modalidad::Virtual
    ): Collection {
        $cohorte->loadMissing('programa.modulos');

        $modulos = $cohorte->programa->modulos;
        if ($modulos->isEmpty()) {
            throw ValidationException::withMessages([
                'cohorte' => 'El programa asociado no tiene módulos registrados para aperturar grupos.',
            ]);
        }

        // Si no se especifica docente, tomar el primer docente activo del sistema como responsable inicial
        $docenteAsignado = $docente ?? User::where('rol', Rol::Docente->value)
            ->where('estado', 'activo')
            ->first();

        if (! $docenteAsignado) {
            throw ValidationException::withMessages([
                'docente' => 'Se requiere al menos un docente registrado en el sistema para la apertura de grupos.',
            ]);
        }

        // Obtener los módulos que ya tienen un grupo en esta cohorte para no duplicarlos
        $modulosConGrupo = $cohorte->grupos()->pluck('modulo_id')->all();
        $modulosParaAperturar = $modulos->reject(fn (Modulo $m) => in_array($m->id, $modulosConGrupo))->values();

        if ($modulosParaAperturar->isEmpty()) {
            return collect();
        }

        $grupos = app(GrupoService::class);

        return DB::transaction(fn () => $modulosParaAperturar->map(fn (Modulo $modulo) => $grupos->crear([
            'modulo_id' => $modulo->id,
            'cohorte_id' => $cohorte->id,
            'docente_id' => $docenteAsignado->id,
            'modalidad' => $modalidad,
            'enlace_videoconferencia' => $modalidad === Modalidad::Virtual ? 'https://meet.example.test/cohorte-'.substr($cohorte->id, 0, 8) : null,
            'cupo_minimo' => $cupoMinimo,
            'estado' => EstadoPlanificacion::Programado,
        ])));
    }

    /**
     * Calcula métricas de ocupación o quórum para un grupo.
     * Si no tiene cupo_minimo configurado, se considera abierto y sin límite.
     *
     * @return array{inscritos: int, minimo: int, alcanzado: bool, porcentaje: float, restante: int, tiene_limite: bool}
     */
    public function calcularQuorum(Grupo $grupo): array
    {
        $inscritos = $grupo->inscripciones_count ?? $grupo->inscripciones()->count();
        $minimo = (int) ($grupo->cupo_minimo ?? 0);
        $tieneLimite = $minimo > 0;
        $alcanzado = $tieneLimite ? ($inscritos >= $minimo) : true;
        $porcentaje = $tieneLimite ? min(100.0, round(($inscritos / $minimo) * 100, 1)) : 100.0;
        $restante = $tieneLimite ? max(0, $minimo - $inscritos) : 0;

        return [
            'inscritos' => $inscritos,
            'minimo' => $minimo,
            'alcanzado' => $alcanzado,
            'porcentaje' => $porcentaje,
            'restante' => $restante,
            'tiene_limite' => $tieneLimite,
        ];
    }

    /**
     * Cambia el estado operativo de una cohorte y opcionalmente sincroniza los grupos asociados.
     */
    public function cambiarEstadoCohorte(Cohorte $cohorte, EstadoPlanificacion $nuevoEstado, bool $propagarAGrupos = false): void
    {
        DB::transaction(function () use ($cohorte, $nuevoEstado, $propagarAGrupos) {
            $cohorte->update(['estado' => $nuevoEstado]);

            if ($propagarAGrupos && $cohorte->grupos()->exists()) {
                $cohorte->grupos()->update(['estado' => $nuevoEstado]);
            }
        });
    }
}
