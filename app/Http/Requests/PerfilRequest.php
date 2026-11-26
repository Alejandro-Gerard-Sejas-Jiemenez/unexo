<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'telefono' => ['nullable', 'string', 'max:'.config('limites.usuarios.telefono')],
            'password' => ['nullable', 'string', 'min:8', 'max:'.config('limites.usuarios.password'), 'confirmed'],
            'foto_perfil_url' => ['nullable', 'string', 'max:255'],
            'foto_perfil' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:2048'],
        ];
    }
}
