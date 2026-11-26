<?php

namespace App\Http\Requests;

use App\Enums\Rol;
use App\Models\Aula;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AulaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === Rol::Administrador;
    }

    public function rules(): array
    {
        $aula = $this->route('aula');

        return [
            'nombre' => [
                'required',
                'string',
                'max:'.config('limites.aulas.nombre'),
                Rule::unique('aulas', 'nombre')->ignore($aula instanceof Aula ? $aula : null),
            ],
            'capacidad' => ['nullable', 'integer', 'min:1', 'max:'.config('limites.aulas.capacidad')],
        ];
    }
}
