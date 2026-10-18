<?php

namespace App\Http\Resources;

use App\Enums\EstadoPlanificacion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso estructurado para la oferta comercial de programas hacia el CRM.
 * @mixin \App\Models\Programa
 */
class CrmProgramaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $modulos = $this->modulos;
        $cohortes = $this->cohortes;

        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'version' => $this->version,
            'descripcion' => $this->descripcion,
            'nivel_academico' => $this->nivel_academico,
            'precio' => $this->precio !== null ? (float) $this->precio : null,
            'moneda' => 'BOB',
            'requisitos_ingreso' => $this->requisitos_ingreso,
            'perfil_egreso' => $this->perfil_egreso,
            'certificado_otorga' => $this->certificado_otorga,
            'imagen_portada_url' => $this->imagen_portada_url,
            'estado_comercial' => $this->estado_comercial->value,
            'total_modulos' => $modulos->count(),
            'duracion_total_horas' => (int) $modulos->sum('duracion_horas'),
            'total_creditos' => (int) $modulos->sum('creditos'),
            'modulos' => $modulos->map(fn ($modulo) => [
                'id' => $modulo->id,
                'codigo' => $modulo->codigo,
                'nombre' => $modulo->nombre,
                'orden' => $modulo->pivot?->orden ?? 1,
                'duracion_horas' => $modulo->duracion_horas,
                'creditos' => $modulo->creditos,
                'tipo_evaluacion' => $modulo->tipo_evaluacion,
                'pdf_temario_url' => $modulo->pdf_temario_url,
                'descripcion' => $modulo->descripcion,
            ])->values(),
            'ediciones_disponibles' => $cohortes
                ?->whereIn('estado', [EstadoPlanificacion::Programado, EstadoPlanificacion::EnCurso])
                ->map(fn ($cohorte) => [
                    'id' => $cohorte->id,
                    'nombre' => $cohorte->nombre,
                    'fecha_inicio' => $cohorte->fecha_inicio?->toDateString(),
                    'fecha_fin' => $cohorte->fecha_fin?->toDateString(),
                    'estado' => $cohorte->estado->value,
                    'estado_label' => $cohorte->estado->label(),
                    'grupos_abiertos_count' => $cohorte->grupos?->count() ?? 0,
                    'grupos' => $cohorte->grupos?->map(fn ($grupo) => [
                        'id' => $grupo->id,
                        'modulo_id' => $grupo->modulo_id,
                        'modulo_nombre' => $grupo->modulo?->nombre,
                        'nombre' => $grupo->nombre,
                        'modalidad' => $grupo->modalidad->value,
                        'fecha_inicio' => $grupo->fecha_inicio?->toDateString(),
                        'fecha_fin' => $grupo->fecha_fin?->toDateString(),
                        'cupo_minimo' => $grupo->cupo_minimo,
                        'aula' => $grupo->aula?->nombre,
                        'docente' => $grupo->docente?->nombre_completo,
                    ])->values() ?? [],
                ])->values() ?? [],
        ];
    }
}
