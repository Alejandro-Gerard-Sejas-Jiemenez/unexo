<?php

namespace App\Services;

use App\Enums\Modalidad;
use App\Models\Grupo;
use App\Models\HorarioGrupo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GrupoService
{
    private const INTENTOS = 5;

    private const NUMERO_MAXIMO = 65535;

    /** @param  array<string, mixed>  $datos  Con `horarios` opcional; el nombre siempre lo asigna el sistema. */
    public function crear(array $datos): Grupo
    {
        $datos = $this->normalizar($datos);

        return $this->conReintento(function () use ($datos): Grupo {
            $numero = $this->siguienteNumero($datos['modulo_id'], $datos['cohorte_id']);
            $horarios = $datos['horarios'] ?? null;

            $grupo = Grupo::create(collect($datos)->except('horarios')->all() + ['nombre' => "Grupo $numero", 'numero' => $numero]);

            if ($horarios !== null) {
                $this->sincronizarHorarios($grupo, $horarios);
            }

            return $grupo;
        });
    }

    /** @param  array<string, mixed>  $datos */
    public function actualizar(Grupo $grupo, array $datos): Grupo
    {
        $datos = $this->normalizar($datos);
        $moduloAntes = $grupo->modulo_id;
        $edicionAntes = $grupo->cohorte_id;

        return $this->conReintento(function () use ($grupo, $datos, $moduloAntes, $edicionAntes): Grupo {
            $horarios = $datos['horarios'] ?? null;
            $cambios = collect($datos)->except('horarios')->all();

            if ($datos['modulo_id'] !== $moduloAntes || $datos['cohorte_id'] !== $edicionAntes) {
                $cambios += $this->nombreEnContextoNuevo($grupo, $datos['modulo_id'], $datos['cohorte_id']);
            }

            $grupo->fill($cambios)->save();

            if ($horarios !== null) {
                $this->sincronizarHorarios($grupo, $horarios);
            }

            return $grupo;
        });
    }

    /** Mayor número entre todos los grupos del contexto (cancelados incluidos) más uno. */
    public function siguienteNumero(string $moduloId, ?string $cohorteId): int
    {
        return ((int) $this->consultaDelContexto($moduloId, $cohorteId)->max('numero')) + 1;
    }

    /** Número de un nombre con formato exacto "Grupo N"; cualquier otro nombre no tiene número. */
    public function numeroDelNombre(string $nombre): ?int
    {
        if (! preg_match('/^Grupo ([1-9][0-9]*)$/D', $nombre, $coincidencia) || (int) $coincidencia[1] > self::NUMERO_MAXIMO) {
            return null;
        }

        return (int) $coincidencia[1];
    }

    /**
     * Reemplaza todos los horarios del grupo por los recibidos, con las horas como HH:MM:SS.
     *
     * @param  array<int, array{dia_semana: int|string, hora_inicio: string, hora_fin: string}>  $horarios
     */
    public function sincronizarHorarios(Grupo $grupo, array $horarios): void
    {
        $grupo->horarios()->delete();

        foreach ($horarios as $horario) {
            HorarioGrupo::create([
                'grupo_id' => $grupo->id,
                'dia_semana' => (int) $horario['dia_semana'],
                'hora_inicio' => $this->conSegundos($horario['hora_inicio']),
                'hora_fin' => $this->conSegundos($horario['hora_fin']),
            ]);
        }

        $grupo->unsetRelation('horarios');
    }

    /** Período (RF-35) y modalidad (RF-24); el nombre y el número nunca vienen de fuera. */
    private function normalizar(array $datos): array
    {
        unset($datos['nombre'], $datos['numero']);

        $datos['cohorte_id'] = ($datos['cohorte_id'] ?? null) ?: null;

        if ($datos['cohorte_id'] !== null) {
            $datos['fecha_inicio'] = null;
            $datos['fecha_fin'] = null;
        }

        $modalidad = $datos['modalidad'] instanceof Modalidad ? $datos['modalidad'] : Modalidad::from($datos['modalidad']);
        if ($modalidad === Modalidad::Virtual) {
            $datos['aula_id'] = null;
        } elseif ($modalidad === Modalidad::Presencial) {
            $datos['enlace_videoconferencia'] = null;
        }

        if (empty($datos['cupo_minimo']) || (int) $datos['cupo_minimo'] <= 0) {
            $datos['cupo_minimo'] = null;
        }

        return $datos;
    }

    /** RF-12: conserva el nombre salvo que ya exista en el contexto nuevo; entonces toma el siguiente número. */
    private function nombreEnContextoNuevo(Grupo $grupo, string $moduloId, ?string $cohorteId): array
    {
        $existe = $this->consultaDelContexto($moduloId, $cohorteId)
            ->where('nombre', $grupo->nombre)
            ->whereKeyNot($grupo->getKey())
            ->exists();

        if ($existe) {
            $numero = $this->siguienteNumero($moduloId, $cohorteId);

            return ['nombre' => "Grupo $numero", 'numero' => $numero];
        }

        return ['nombre' => $grupo->nombre, 'numero' => $this->numeroDelNombre($grupo->nombre)];
    }

    private function consultaDelContexto(string $moduloId, ?string $cohorteId): Builder
    {
        return Grupo::where('modulo_id', $moduloId)
            ->when($cohorteId, fn ($q) => $q->where('cohorte_id', $cohorteId), fn ($q) => $q->whereNull('cohorte_id'));
    }

    /** Repite la transacción completa si otra petición tomó el mismo número entre el cálculo y el guardado (RF-13). */
    private function conReintento(callable $operacion): Grupo
    {
        for ($intento = 1; $intento <= self::INTENTOS; $intento++) {
            try {
                return DB::transaction($operacion);
            } catch (UniqueConstraintViolationException) {
                continue;
            }
        }

        throw ValidationException::withMessages([
            'modulo_id' => 'No se pudo asignar el nombre del grupo. Intenta de nuevo.',
        ]);
    }

    private function conSegundos(string $hora): string
    {
        return strlen($hora) === 5 ? $hora.':00' : $hora;
    }
}
