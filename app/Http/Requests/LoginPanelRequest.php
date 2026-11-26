<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginPanelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'usuario' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string'],
            'recordar' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'usuario' => 'correo',
            'password' => 'contraseña',
        ];
    }
}
