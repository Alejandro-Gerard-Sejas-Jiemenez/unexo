<?php

namespace App\Http\Requests;

use App\Enums\Rol;
use Illuminate\Foundation\Http\FormRequest;

class ComprobanteStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === Rol::Estudiante;
    }

    public function rules(): array
    {
        return [
            'archivo' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ];
    }
}
