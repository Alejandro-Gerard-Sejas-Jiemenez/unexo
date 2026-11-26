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

    public function rules(): array
    {
        return [
            'metodo_pago_id' => ['nullable', 'uuid', Rule::exists('metodos_pago', 'id')],
            'monto' => ['sometimes', 'numeric', 'min:0.01', 'max:999999.99'],
        ];
    }

    public function attributes(): array
    {
        return [
            'metodo_pago_id' => 'método de pago',
            'monto' => 'monto',
        ];
    }

    public function messages(): array
    {
        return [
            'monto.min' => 'El monto debe ser mayor que cero.',
        ];
    }
}
