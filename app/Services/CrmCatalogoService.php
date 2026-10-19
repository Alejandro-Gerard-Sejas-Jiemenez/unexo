<?php

namespace App\Services;

use App\Enums\EstadoComercial;
use App\Enums\EstadoPlanificacion;
use App\Models\Cohorte;
use App\Models\Modulo;
use App\Models\Programa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

/**
 * Consultas de la oferta visible para el CRM, con sus relaciones cargadas de una vez.
 */
class CrmCatalogoService
{
    /** @return Collection<int, Programa> */
    public function programasVisibles(?string $busqueda = null): Collection
    {
        return $this->consultaProgramas()
            ->when($busqueda, fn (Builder $q) => $q->where(fn (Builder $b) => $b
                ->where('nombre', 'like', "%{$busqueda}%")
                ->orWhere('codigo', 'like', "%{$busqueda}%")
                ->orWhere('descripcion', 'like', "%{$busqueda}%")
            ))
            ->orderBy('nombre')
            ->get();
    }

    public function programaVisible(string $id): Programa
    {
        return $this->consultaProgramas()->findOrFail($id);
    }

    /** @return Collection<int, Modulo> */
    public function modulosVisibles(?string $busqueda = null): Collection
    {
        return $this->consultaModulos()
            ->when($busqueda, fn (Builder $q) => $q->where(fn (Builder $b) => $b
                ->where('nombre', 'like', "%{$busqueda}%")
                ->orWhere('codigo', 'like', "%{$busqueda}%")
                ->orWhere('descripcion', 'like', "%{$busqueda}%")
            ))
            ->orderBy('nombre')
            ->get();
    }

    public function moduloVisible(string $id): Modulo
    {
        return $this->consultaModulos()->findOrFail($id);
    }

    /**
     * Ediciones programadas o en curso de programas visibles; los filtros desconocidos se ignoran.
     *
     * @return Collection<int, Cohorte>
     */
    public function edicionesVisibles(?string $programaId = null, ?string $estado = null): Collection
    {
        $estadoFiltro = in_array($estado, $this->estadosVisibles(), true) ? $estado : null;

        return $this->consultaEdiciones()
            ->when(Str::isUuid((string) $programaId), fn (Builder $q) => $q->where('programa_id', $programaId))
            ->when($estadoFiltro, fn (Builder $q) => $q->where('estado', $estadoFiltro))
            ->orderBy('fecha_inicio')
            ->orderBy('id')
            ->get();
    }

    public function edicionVisible(string $id): Cohorte
    {
        return $this->consultaEdiciones()->findOrFail($id);
    }

    private function consultaEdiciones(): Builder
    {
        return Cohorte::visibleParaCrm()->with([
            'programa:id,codigo,nombre,version',
            'programa.modulos:modulos.id',
            'grupos' => fn (Relation $q) => $this->grupos($q),
            'grupos.modulo:id,nombre',
            'grupos.cohorte:id,fecha_inicio,fecha_fin',
            'grupos.horarios',
            'grupos.docente:id,nombres,apellidos',
            'grupos.aula',
        ]);
    }

    private function consultaProgramas(): Builder
    {
        return Programa::visibleParaCrm()->with([
            'modulos' => fn (Relation $q) => $q->where('modulos.estado_comercial', EstadoComercial::Abierto->value),
            'cohortes' => fn (Relation $q) => $q
                ->whereIn('estado', $this->estadosVisibles())
                ->orderBy('fecha_inicio'),
            'cohortes.programa:id',
            'cohortes.programa.modulos:modulos.id',
            'cohortes.grupos' => fn (Relation $q) => $this->grupos($q),
            'cohortes.grupos.modulo:id,nombre',
            'cohortes.grupos.cohorte:id,fecha_inicio,fecha_fin',
            'cohortes.grupos.horarios',
            'cohortes.grupos.docente:id,nombres,apellidos',
            'cohortes.grupos.aula',
        ]);
    }

    private function consultaModulos(): Builder
    {
        return Modulo::visibleParaCrm()->with([
            'programas' => fn (Relation $q) => $q->visibleParaCrm(),
            'grupos' => fn (Relation $q) => $this->grupos($q)->sueltos(),
            'grupos.modulo:id,nombre',
            'grupos.horarios',
            'grupos.docente:id,nombres,apellidos',
            'grupos.aula',
        ]);
    }

    /** Solo grupos programados o en curso, ordenados por su período efectivo. */
    private function grupos(Relation|Builder $consulta): Relation|Builder
    {
        return $consulta->visibleParaCrm()->ordenadoPorPeriodo()->orderBy('nombre');
    }

    /** @return list<string> */
    private function estadosVisibles(): array
    {
        return array_map(fn (EstadoPlanificacion $estado) => $estado->value, EstadoPlanificacion::visiblesParaCrm());
    }
}
