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
            'docente_id' => ['nullable', 'uuid', DocenteGrupoRequest::docenteActivo()],
            // Mapa módulo => docente. Si llega, debe cubrir todos los módulos pendientes.
            'docentes' => ['nullable', 'array'],
            'docentes.*' => ['required', 'uuid', DocenteGrupoRequest::docenteActivo()],
            'modalidad' => ['nullable', Rule::enum(Modalidad::class)],
            'cupo_minimo' => ['nullable', 'integer', 'min:0', 'max:'.config('limites.grupos.cupo_minimo')],
        ];
    }

    public function messages(): array
    {
        return [
            'docente_id.exists' => 'Elige un docente activo.',
            'docentes.*.exists' => 'Elige un docente activo.',
        ];
    }
}
