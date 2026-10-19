<?php

namespace App\Http\Resources;

use App\Models\Grupo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Grupo publicado al CRM: período efectivo, horarios y solo el nombre del docente.
 *
 * @mixin Grupo
 */
class CrmGrupoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'modulo_id' => $this->modulo_id,
            'modulo_nombre' => $this->modulo?->nombre,
            'nombre' => $this->nombre,
            'modalidad' => $this->modalidad->value,
            'fecha_inicio' => $this->periodo_inicio?->toDateString(),
            'fecha_fin' => $this->periodo_fin?->toDateString(),
            'cupo_minimo' => $this->cupo_minimo,
            'aula' => $this->aula?->nombre,
            'docente' => $this->docente?->nombre_completo,
            'horarios' => $this->horarios->map(fn ($horario) => [
                'dia_semana' => $horario->dia_semana->value,
                'dia' => $horario->dia_semana->enMensaje(),
                'hora_inicio' => substr($horario->hora_inicio, 0, 5),
                'hora_fin' => substr($horario->hora_fin, 0, 5),
            ])->values(),
        ];
    }
}
