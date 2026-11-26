<?php

namespace App\Http\Requests;

use App\Enums\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class DocenteGrupoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === Rol::Administrador;
    }

    public function rules(): array
    {
        return [
            'docente_id' => ['required', 'uuid', self::docenteActivo()],
        ];
    }

    public function messages(): array
    {
        return [
            'docente_id.exists' => 'Elige un docente activo.',
        ];
    }

    /** Solo se puede asignar un usuario activo con rol Docente. */
    public static function docenteActivo(): Exists
    {
        return Rule::exists('users', 'id')->where('rol', Rol::Docente->value)->where('estado', 'activo');
    }
}
