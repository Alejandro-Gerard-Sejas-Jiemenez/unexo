<?php

namespace App\Http\Requests;

use App\Enums\EstadoComercial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreModuloRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el acceso lo controla el middleware rol: en routes/web.php
    }

    public function rules(): array
    {
        return [
            'codigo' => ['required', 'string', 'max:'.config('limites.modulos.codigo'), Rule::unique('modulos', 'codigo')],
            'nombre' => ['required', 'string', 'max:'.config('limites.modulos.nombre')],
            'duracion_horas' => ['nullable', 'integer', 'min:1', 'max:'.config('limites.modulos.duracion_horas')],
            'estado_comercial' => ['required', Rule::enum(EstadoComercial::class)],
            'descripcion' => ['nullable', 'string'],
            'prerrequisito' => ['nullable', 'string', 'max:'.config('limites.modulos.prerrequisito')],
            'pdf_temario_url' => ['nullable', 'url', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'prerrequisito.max' => 'El prerrequisito no puede tener más de :max caracteres.',
        ];
    }
}
