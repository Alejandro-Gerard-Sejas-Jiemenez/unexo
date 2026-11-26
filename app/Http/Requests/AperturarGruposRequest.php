<?php

namespace App\Http\Requests;

use App\Enums\Modalidad;
use App\Enums\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AperturarGruposRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === Rol::Administrador;
    }

    public function rules(): array
    {
        return [
            'docente_id' => ['nullable', 'uuid', Rule::exists('users', 'id')->where('rol', Rol::Docente->value)],
            'modalidad' => ['nullable', Rule::enum(Modalidad::class)],
            'cupo_minimo' => ['nullable', 'integer', 'min:0', 'max:'.config('limites.grupos.cupo_minimo')],
        ];
    }
}
