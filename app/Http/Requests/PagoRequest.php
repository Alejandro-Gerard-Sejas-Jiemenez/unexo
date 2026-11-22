<?php

namespace App\Http\Requests;

use App\Enums\Rol;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === Rol::Administrador;
    }

    public function rules(): array
    {
        return [
            'metodo_pago_id' => ['nullable', 'uuid', Rule::exists('metodos_pago', 'id')],
            'monto' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            // Sin cuotas es un pago único; con cuotas se genera el plan de pagos.
            'cuotas' => ['nullable', 'integer', 'min:2', 'max:36'],
        ];
    }

    public function attributes(): array
    {
        return [
            'metodo_pago_id' => 'método de pago',
            'monto' => 'monto',
            'cuotas' => 'número de cuotas',
        ];
    }

    public function messages(): array
    {
        return [
            'monto.min' => 'El monto debe ser mayor que cero.',
            'cuotas.min' => 'Un plan de pagos necesita al menos 2 cuotas.',
        ];
    }
}
