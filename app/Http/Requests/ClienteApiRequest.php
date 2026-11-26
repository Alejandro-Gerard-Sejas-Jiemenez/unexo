<?php

namespace App\Http\Requests;

use App\Enums\Rol;
use Illuminate\Foundation\Http\FormRequest;

class ClienteApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === Rol::Administrador;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:80', 'unique:api_clientes,nombre'],
        ];
    }
}
