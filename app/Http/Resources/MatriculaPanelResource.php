<?php

namespace App\Http\Resources;

use App\Models\Matricula;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Matrícula vista desde el panel. Distinta de MatriculaResource, que es la que
 * consume el CRM y lleva solo lo comercial.
 *
 * @mixin Matricula
 */
class MatriculaPanelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'estudiante_id' => $this->estudiante_id,
            'estudiante' => $this->whenLoaded('estudiante', fn () => [
                'id' => $this->estudiante->id,
                'nombre' => $this->estudiante->nombre_completo,
                'email' => $this->estudiante->email,
                'correo_institucional' => $this->estudiante->correo_institucional,
            ]),
            'cohorte_id' => $this->cohorte_id,
            'cohorte' => $this->whenLoaded('cohorte', fn () => $this->cohorte ? [
                'id' => $this->cohorte->id,
                'nombre' => $this->cohorte->nombre,
                'programa' => $this->cohorte->relationLoaded('programa')
                    ? $this->cohorte->programa?->nombre
                    : null,
                'precio' => $this->cohorte->relationLoaded('programa') && $this->cohorte->programa?->precio !== null
                    ? (float) $this->cohorte->programa->precio
                    : null,
            ] : null),
            'importe' => $this->importe(),
            'estado' => $this->estado->value,
            'estado_label' => $this->estado->label(),
            'origen' => $this->origen->value,
            'origen_label' => $this->origen->label(),
            'id_externo_crm' => $this->id_externo_crm,
            'fecha_matricula' => $this->fecha_matricula?->format('Y-m-d'),
            'fecha_retiro' => $this->fecha_retiro?->format('Y-m-d'),
            'inscripciones_count' => $this->whenCounted('inscripciones'),
            'inscripciones' => $this->whenLoaded('inscripciones', fn () => $this->inscripciones->map(fn ($i) => [
                'id' => $i->id,
                'grupo_id' => $i->grupo_id,
                'grupo' => $i->relationLoaded('grupo') ? $i->grupo?->nombre : null,
                'periodo_texto' => $i->relationLoaded('grupo') ? $i->grupo?->periodo_texto : null,
                'modulo' => $i->relationLoaded('grupo') && $i->grupo?->relationLoaded('modulo')
                    ? $i->grupo->modulo?->nombre
                    : null,
                'docente' => $i->relationLoaded('grupo') && $i->grupo?->relationLoaded('docente')
                    ? $i->grupo->docente?->nombre_completo
                    : null,
            ])->values()),
            'comprobantes' => $this->whenLoaded('comprobantes', fn () => $this->comprobantes->map(fn ($c) => [
                'id' => $c->id,
                'estado' => $c->estado->value,
                'estado_label' => $c->estado->label(),
                'url_archivo' => url('/api/panel/comprobantes/'.$c->id),
                'subido_por' => $c->relationLoaded('subidoPor') ? $c->subidoPor?->nombre_completo : null,
                'fecha_verificacion' => $c->fecha_verificacion?->format('d/m/Y H:i'),
                'creado' => $c->created_at?->format('d/m/Y H:i'),
            ])->values()),
        ];
    }
}
