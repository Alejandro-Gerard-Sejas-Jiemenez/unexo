<?php

namespace App\Services;

use App\Enums\EstadoPlanificacion;
use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Modulo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlanificacionCohorteService
{
    /**
     * Apertura asistida de los grupos para todos los módulos del programa de la cohorte.
     * Mantiene el orden oficial del programa y calcula tramos de fechas proporcionales sugeridos.
     *
     * @return Collection<int, Grupo>
     */
    public function generarGruposParaCohorte(
        Cohorte $cohorte,
        ?User $docente = null,
        int $cupoMinimo = 20,
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

        return DB::transaction(function () use ($cohorte, $modulosParaAperturar, $docenteAsignado, $cupoMinimo, $modalidad) {
            $totalModulos = $modulosParaAperturar->count();
            $fechaInicioGlobal = Carbon::parse($cohorte->fecha_inicio);
            $fechaFinGlobal = Carbon::parse($cohorte->fecha_fin);

            $diasTotales = max(1, $fechaInicioGlobal->diffInDays($fechaFinGlobal));
            $diasPorModulo = max(1, (int) floor($diasTotales / $totalModulos));

            $gruposCreados = collect();

            foreach ($modulosParaAperturar as $indice => $modulo) {
                $inicioModulo = $fechaInicioGlobal->copy()->addDays($indice * $diasPorModulo);
                $finModulo = ($indice === $totalModulos - 1)
                    ? $fechaFinGlobal->copy()
                    : $inicioModulo->copy()->addDays($diasPorModulo - 1);

                // Si el cálculo sobrepasa la fecha final de la cohorte, ajustar a la fecha final
                if ($finModulo->gt($fechaFinGlobal)) {
                    $finModulo = $fechaFinGlobal->copy();
                }

                $grupo = Grupo::create([
                    'modulo_id' => $modulo->id,
                    'cohorte_id' => $cohorte->id,
                    'docente_id' => $docenteAsignado->id,
                    'nombre' => 'Grupo A',
                    'fecha_inicio' => $inicioModulo->format('Y-m-d'),
                    'fecha_fin' => $finModulo->format('Y-m-d'),
                    'modalidad' => $modalidad,
                    'enlace_videoconferencia' => $modalidad === Modalidad::Virtual ? 'https://meet.example.test/cohorte-'.substr($cohorte->id, 0, 8) : null,
                    'cupo_minimo' => $cupoMinimo,
                    'estado' => EstadoPlanificacion::Programado,
                ]);

                $gruposCreados->push($grupo);
            }

            return $gruposCreados;
        });
    }

    /**
     * Calcula métricas de quórum operativo para un grupo según la regla de negocio de NEXO (20 inscritos mín.).
     *
     * @return array{inscritos: int, minimo: int, alcanzado: bool, porcentaje: float, restante: int}
     */
    public function calcularQuorum(Grupo $grupo): array
    {
        $inscritos = $grupo->inscripciones_count ?? $grupo->inscripciones()->count();
        $minimo = $grupo->cupo_minimo ?? 20;
        $alcanzado = $inscritos >= $minimo;
        $porcentaje = $minimo > 0 ? min(100.0, round(($inscritos / $minimo) * 100, 1)) : 100.0;
        $restante = max(0, $minimo - $inscritos);

        return [
            'inscritos' => $inscritos,
            'minimo' => $minimo,
            'alcanzado' => $alcanzado,
            'porcentaje' => $porcentaje,
            'restante' => $restante,
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
