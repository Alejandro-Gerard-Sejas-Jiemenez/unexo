<?php

namespace App\Http\Requests;

use App\Enums\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MatriculaStoreApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === Rol::Administrador;
    }

    public function rules(): array
    {
        return [
            'estudiante_id' => ['required', 'uuid', Rule::exists('users', 'id')->where('rol', Rol::Estudiante->value)],
            'tipo' => ['required', 'in:cohorte,suelto'],
            'cohorte_id' => ['required_if:tipo,cohorte', 'nullable', 'uuid', Rule::exists('cohortes', 'id')],
            'grupo_id' => ['required_if:tipo,suelto', 'nullable', 'uuid', Rule::exists('grupos', 'id')->whereNull('cohorte_id')],
        ];
    }
}
