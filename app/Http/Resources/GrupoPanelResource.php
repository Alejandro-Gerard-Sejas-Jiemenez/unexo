<?php

namespace App\Http\Resources;

use App\Models\Grupo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Grupo
 */
class GrupoPanelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'numero' => $this->numero,
            'modulo_id' => $this->modulo_id,
            'modulo' => $this->whenLoaded('modulo', fn () => [
                'id' => $this->modulo->id,
                'nombre' => $this->modulo->nombre,
                'codigo' => $this->modulo->codigo,
            ]),
            'cohorte_id' => $this->cohorte_id,
            'cohorte' => $this->whenLoaded('cohorte', fn () => $this->cohorte ? [
                'id' => $this->cohorte->id,
                'nombre' => $this->cohorte->nombre,
                'programa' => $this->cohorte->relationLoaded('programa')
                    ? $this->cohorte->programa?->nombre
                    : null,
            ] : null),
            'docente_id' => $this->docente_id,
            'docente' => $this->whenLoaded('docente', fn () => $this->docente?->nombre_completo),
            'aula_id' => $this->aula_id,
            'aula' => $this->whenLoaded('aula', fn () => $this->aula?->nombre),
            'fecha_inicio' => $this->fecha_inicio?->format('Y-m-d'),
            'fecha_fin' => $this->fecha_fin?->format('Y-m-d'),
            'modalidad' => $this->modalidad->value,
            'modalidad_label' => ucfirst($this->modalidad->value),
            'enlace_videoconferencia' => $this->enlace_videoconferencia,
            'cupo_minimo' => $this->cupo_minimo,
            'estado' => $this->estado->value,
            'estado_label' => $this->estado->label(),
            'inscripciones_count' => $this->whenCounted('inscripciones'),
            'horarios' => $this->whenLoaded('horarios', fn () => $this->horarios->map(fn ($h) => [
                'dia_semana' => $h->dia_semana instanceof \BackedEnum ? $h->dia_semana->value : $h->dia_semana,
                'dia_label' => $h->dia_semana instanceof \BackedEnum ? $h->dia_semana->label() : null,
                'hora_inicio' => substr((string) $h->hora_inicio, 0, 5),
                'hora_fin' => substr((string) $h->hora_fin, 0, 5),
            ])->values()),
        ];
    }
}
