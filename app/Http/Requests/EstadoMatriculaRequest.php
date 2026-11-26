<?php

namespace App\Http\Requests;

use App\Enums\EstadoMatricula;
use App\Enums\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EstadoMatriculaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === Rol::Administrador;
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::enum(EstadoMatricula::class)],
        ];
    }
}
