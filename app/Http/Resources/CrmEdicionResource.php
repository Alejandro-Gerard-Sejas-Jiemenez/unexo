<?php

namespace App\Http\Resources;

use App\Models\Cohorte;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * Edición (cohorte) publicada al CRM con sus grupos programados o en curso.
 *
 * @mixin Cohorte
 */
class CrmEdicionResource extends JsonResource
{
    private bool $conPrograma = true;

    /** Dentro de un programa se omite el programa contenedor; la relación solo sirve para ordenar. */
    public function sinPrograma(): static
    {
        $this->conPrograma = false;

        return $this;
    }

    public function toArray(Request $request): array
    {
        $grupos = $this->gruposOrdenados();

        return [
            'id' => $this->id,
            ...($this->conPrograma && $this->relationLoaded('programa') ? ['programa' => [
                'id' => $this->programa->id,
                'codigo' => $this->programa->codigo,
                'nombre' => $this->programa->nombre,
                'version' => $this->programa->version,
            ]] : []),
            'nombre' => $this->nombre,
            'fecha_inicio' => $this->fecha_inicio?->toDateString(),
            'fecha_fin' => $this->fecha_fin?->toDateString(),
            'estado' => $this->estado->value,
            'estado_label' => $this->estado->label(),
            'grupos_abiertos_count' => $grupos->count(),
            'grupos' => CrmGrupoResource::collection($grupos),
        ];
    }

    /** Por posición del módulo en el programa y luego por número, nombre e id. */
    private function gruposOrdenados(): Collection
    {
        $posiciones = $this->relationLoaded('programa')
            ? $this->programa->modulos->pluck('id')->flip()
            : collect();

        return $this->grupos
            ->sortBy(fn ($grupo) => [
                $posiciones->get($grupo->modulo_id, PHP_INT_MAX),
                $grupo->numero ?? PHP_INT_MAX,
                $grupo->nombre,
                $grupo->id,
            ])
            ->values();
    }
}
