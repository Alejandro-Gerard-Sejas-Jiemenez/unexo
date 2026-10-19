<?php

namespace App\Http\Requests;

use App\Enums\DiaSemana;
use App\Enums\EstadoPlanificacion;
use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Models\Aula;
use App\Models\Cohorte;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class StoreGrupoRequest extends FormRequest
{
    private const FORMATO_HORA = '/^([01]\d|2[0-3]):[0-5]\d$/';

    public function authorize(): bool
    {
        return true; // el acceso lo controla el middleware rol: en routes/web.php
    }

    public function rules(): array
    {
        return [
            'modulo_id' => ['required', 'uuid', Rule::exists('modulos', 'id')],
            'cohorte_id' => ['nullable', 'uuid', Rule::exists('cohortes', 'id')],
            'docente_id' => ['required', 'uuid', Rule::exists('users', 'id')->where('rol', Rol::Docente->value)],
            'aula_id' => [Rule::requiredIf(fn () => $this->input('modalidad') === Modalidad::Presencial->value), 'nullable', 'uuid', Rule::exists('aulas', 'id')],
            'modalidad' => ['required', Rule::enum(Modalidad::class)],
            'enlace_videoconferencia' => ['nullable', 'url', 'max:'.config('limites.grupos.enlace_videoconferencia')],
            'cupo_minimo' => ['nullable', 'integer', 'min:0', 'max:'.config('limites.grupos.cupo_minimo')],
            'estado' => ['required', Rule::enum(EstadoPlanificacion::class)],
            'horarios' => [Rule::requiredIf(fn () => $this->exigeHorarios()), 'array', 'max:'.config('limites.grupos.horarios_max')],
            'horarios.*.dia_semana' => ['required', Rule::enum(DiaSemana::class)],
            'horarios.*.hora_inicio' => ['required', 'date_format:H:i'],
            'horarios.*.hora_fin' => ['required', 'date_format:H:i'],
        ] + $this->reglasDeFechas();
    }

    public function messages(): array
    {
        return [
            'aula_id.required' => 'Debe asignar un aula física para grupos en modalidad presencial.',
            'fecha_inicio.required' => 'Indica la fecha de inicio.',
            'fecha_fin.required' => 'Indica la fecha de fin.',
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la de inicio.',
            'horarios.required' => 'Agrega al menos un horario.',
            'horarios.max' => 'Un grupo puede tener hasta :max horarios.',
            'horarios.*.dia_semana.required' => 'Elige el día de la semana.',
            'horarios.*.dia_semana.enum' => 'Elige el día de la semana.',
            'horarios.*.hora_inicio.required' => 'Indica la hora de inicio con el formato HH:MM.',
            'horarios.*.hora_inicio.date_format' => 'Indica la hora de inicio con el formato HH:MM.',
            'horarios.*.hora_fin.required' => 'Indica la hora de fin con el formato HH:MM.',
            'horarios.*.hora_fin.date_format' => 'Indica la hora de fin con el formato HH:MM.',
        ];
    }

    /** @return array<int, callable> */
    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validarPertenenciaDelModulo($validator),
            fn (Validator $validator) => $this->validarCapacidadDelAula($validator),
            fn (Validator $validator) => $this->validarHorarios($validator),
        ];
    }

    /** Con edición el período es el de la edición: no se piden fechas y las enviadas se descartan (RF-15, RF-35). */
    private function reglasDeFechas(): array
    {
        if (filled($this->input('cohorte_id'))) {
            return [];
        }

        return [
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ];
    }

    private function exigeHorarios(): bool
    {
        return in_array($this->input('estado'), [EstadoPlanificacion::Programado->value, EstadoPlanificacion::EnCurso->value], true);
    }

    private function validarPertenenciaDelModulo(Validator $validator): void
    {
        $errores = $validator->errors();
        if ($errores->hasAny(['modulo_id', 'cohorte_id']) || blank($this->input('cohorte_id')) || blank($this->input('modulo_id'))) {
            return;
        }

        $programa = Cohorte::with('programa')->find($this->input('cohorte_id'))?->programa;
        if ($programa && ! $programa->modulos()->where('modulos.id', $this->input('modulo_id'))->exists()) {
            $errores->add('modulo_id', 'El módulo seleccionado no pertenece al programa de esta cohorte ('.$programa->nombre.').');
        }
    }

    /** El aforo solo importa con aula física: virtual la descarta (RF-24). */
    private function validarCapacidadDelAula(Validator $validator): void
    {
        $errores = $validator->errors();
        $cupo = (int) $this->input('cupo_minimo');
        if ($errores->hasAny(['aula_id', 'modalidad', 'cupo_minimo']) || $cupo <= 0 || blank($this->input('aula_id'))
            || $this->input('modalidad') === Modalidad::Virtual->value) {
            return;
        }

        $aula = Aula::find($this->input('aula_id'));
        if ($aula && $aula->capacidad && $aula->capacidad < $cupo) {
            $errores->add('aula_id', "La capacidad del aula ({$aula->capacidad} personas) es inferior al cupo mínimo configurado ({$cupo}).");
        }
    }

    /** Primero todos los fines no posteriores al inicio (RF-20); luego los solapes entre filas válidas (RF-21). */
    private function validarHorarios(Validator $validator): void
    {
        $errores = $validator->errors();

        $validas = [];
        foreach ($this->filasConFormatoValido() as $indice => $fila) {
            if ($fila['fin'] <= $fila['inicio']) {
                $errores->add("horarios.$indice.hora_fin", "En {$fila['dia']->enMensaje()}, la hora de fin debe ser posterior a la de inicio.");

                continue;
            }
            $validas[$indice] = $fila;
        }

        foreach (collect($validas)->groupBy(fn (array $fila) => $fila['dia']->value) as $delDia) {
            $this->marcarPrimerSolape($validator, $delDia->sortBy('inicio'));
        }
    }

    /** @return array<int, array{dia: DiaSemana, inicio: string, fin: string}> */
    private function filasConFormatoValido(): array
    {
        $filas = [];
        foreach ((array) $this->input('horarios', []) as $indice => $fila) {
            if (! is_array($fila)) {
                continue;
            }

            $dia = DiaSemana::tryFrom((int) ($fila['dia_semana'] ?? 0));
            $inicio = (string) ($fila['hora_inicio'] ?? '');
            $fin = (string) ($fila['hora_fin'] ?? '');

            if ($dia && preg_match(self::FORMATO_HORA, $inicio) && preg_match(self::FORMATO_HORA, $fin)) {
                $filas[$indice] = compact('dia', 'inicio', 'fin');
            }
        }

        return $filas;
    }

    /** Un solo mensaje por día, sobre la fila posterior del primer solape. */
    private function marcarPrimerSolape(Validator $validator, Collection $filasDelDia): void
    {
        $finMaximo = null;
        foreach ($filasDelDia as $indice => $fila) {
            if ($finMaximo !== null && $fila['inicio'] < $finMaximo) {
                $validator->errors()->add("horarios.$indice.hora_inicio", "En {$fila['dia']->enMensaje()}, los horarios se solapan.");

                return;
            }
            $finMaximo = max($finMaximo ?? $fila['fin'], $fila['fin']);
        }
    }
}
