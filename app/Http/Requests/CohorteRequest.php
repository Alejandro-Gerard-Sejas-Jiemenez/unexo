<?php

namespace App\Http\Requests;

use App\Enums\EstadoPlanificacion;
use App\Models\Cohorte;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Reglas de una edición (cohorte): una sola fuente para el panel Blade y la API.
 */
class CohorteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso lo controla el middleware rol:
    }

    private function cohorteActual(): ?Cohorte
    {
        $cohorte = $this->route('cohorte');

        return $cohorte instanceof Cohorte ? $cohorte : null;
    }

    public function rules(): array
    {
        return [
            'programa_id' => ['required', 'uuid', Rule::exists('programas', 'id')],
            // En el alta el nombre se puede omitir: el modelo lo genera.
            'nombre' => [$this->cohorteActual() ? 'required' : 'nullable', 'string', 'max:'.config('limites.cohortes.nombre')],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'estado' => ['required', Rule::enum(EstadoPlanificacion::class)],
        ];
    }

    public function attributes(): array
    {
        return [
            'programa_id' => 'programa',
            'fecha_inicio' => 'fecha de inicio',
            'fecha_fin' => 'fecha de fin',
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la de inicio.',
        ];
    }

    /** En la edición, un nombre vacío conserva el actual en vez de borrarlo. */
    public function datosParaGuardar(): array
    {
        $datos = $this->validated();

        if (empty($datos['nombre'])) {
            $actual = $this->cohorteActual();
            $datos['nombre'] = $actual
                ? $actual->nombre
                : Cohorte::generarNombreEdicion($datos['programa_id']);
        }

        return $datos;
    }
}
