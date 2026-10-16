<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Matricula */
class MatriculaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'estado' => $this->estado->value,
            'origen' => $this->origen->value,
            'fecha_matricula' => $this->fecha_matricula->toDateString(),
            'fecha_retiro' => $this->fecha_retiro?->toDateString(),
            'estudiante' => [
                'id' => $this->estudiante->id,
                'id_externo_crm' => $this->estudiante->id_externo_crm,
                'nombre' => $this->estudiante->nombre_completo,
                'email' => $this->estudiante->email,
            ],
            'programa' => $this->cohorte?->programa?->nombre,
            'cohorte' => $this->cohorte?->nombre,
            'grupos' => $this->inscripciones->map(fn ($i) => [
                'id' => $i->grupo->id,
                'modulo' => $i->grupo->modulo->nombre,
                'nombre' => $i->grupo->nombre,
                'fecha_inicio' => $i->grupo->fecha_inicio->toDateString(),
                'fecha_fin' => $i->grupo->fecha_fin->toDateString(),
            ])->values(),
        ];
    }
}
