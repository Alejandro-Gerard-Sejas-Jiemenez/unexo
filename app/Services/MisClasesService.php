<?php

namespace App\Services;

use App\Enums\EstadoMatricula;
use App\Models\Grupo;
use App\Models\InscripcionGrupo;
use App\Models\User;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MisClasesService
{
    public const FASES = ['por-iniciar', 'en-curso', 'finalizado'];

    public function inscripcionesDelEstudiante(User $estudiante): Collection
    {
        return InscripcionGrupo::query()
            ->whereHas('matricula', fn ($q) => $q->where('estudiante_id', $estudiante->id))
            ->with(['matricula', 'grupo.modulo', 'grupo.docente', 'grupo.aula', 'grupo.cohorte.programa'])
            ->get()
            ->sortBy(fn (InscripcionGrupo $inscripcion) => $inscripcion->grupo->fecha_inicio)
            ->values();
    }

    public function gruposDelDocente(User $docente): Collection
    {
        return Grupo::query()
            ->where('docente_id', $docente->id)
            ->with(['modulo', 'cohorte.programa', 'aula'])
            ->withCount(['inscripciones as inscritos_count' => fn ($q) => $q->whereHas(
                'matricula',
                fn ($matricula) => $matricula->where('estado', '!=', EstadoMatricula::Retirado->value),
            )])
            ->orderBy('fecha_inicio')
            ->get();
    }

    /** Estudiantes inscritos en un grupo, ordenados por apellido. */
    public function estudiantesDelGrupo(Grupo $grupo): Collection
    {
        return $grupo->inscripciones()
            ->with('matricula.estudiante')
            ->get()
            ->sortBy(fn (InscripcionGrupo $inscripcion) => Str::lower($inscripcion->matricula->estudiante->apellidos.' '.$inscripcion->matricula->estudiante->nombres))
            ->values();
    }

    /** @param Closure(mixed): Grupo $grupoDe  Obtiene el grupo de cada elemento (inscripción o grupo). */
    public function filtrar(Collection $elementos, Closure $grupoDe, ?string $fase, string $busqueda): Collection
    {
        $busqueda = Str::lower(trim($busqueda));

        return $elementos
            ->filter(fn ($elemento) => $busqueda === '' || Str::contains($this->textoBuscable($grupoDe($elemento)), $busqueda))
            ->filter(fn ($elemento) => ! in_array($fase, self::FASES, true) || $grupoDe($elemento)->fase === $fase)
            ->values();
    }

    /** Los primeros elementos que aún no terminaron ni se cancelaron, para el inicio. */
    public function proximos(Collection $elementos, Closure $grupoDe, int $limite = 3): Collection
    {
        return $elementos
            ->filter(fn ($elemento) => in_array($grupoDe($elemento)->fase, ['por-iniciar', 'en-curso'], true))
            ->take($limite)
            ->values();
    }

    /** Cantidad por fase, para los filtros. Los cancelados no cuentan en ninguna fase. */
    public function conteoPorFase(Collection $elementos, Closure $grupoDe): array
    {
        $conteo = array_fill_keys(self::FASES, 0);

        foreach ($elementos as $elemento) {
            $fase = $grupoDe($elemento)->fase;
            if (isset($conteo[$fase])) {
                $conteo[$fase]++;
            }
        }

        return $conteo;
    }

    private function textoBuscable(Grupo $grupo): string
    {
        return Str::lower(implode(' ', array_filter([
            $grupo->modulo->nombre,
            $grupo->nombre,
            $grupo->cohorte?->programa?->nombre,
            $grupo->docente?->nombre_completo,
        ])));
    }
}
