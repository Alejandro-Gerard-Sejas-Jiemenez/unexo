<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EstudiantePagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    // El importe lo fija el sistema: cualquier «monto» enviado se ignora.
    public function rules(): array
    {
        return [
            'metodo_pago_id' => ['nullable', 'uuid', Rule::exists('metodos_pago', 'id')],
        ];
    }

    public function attributes(): array
    {
        return [
            'metodo_pago_id' => 'método de pago',
        ];
    }
}
