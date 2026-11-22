<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MetodoPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'tipo' => ['required', Rule::in(['tarjeta', 'qr', 'transferencia'])],
            'titular' => ['required', 'string', 'max:120'],
            // Solo se usa para quedarnos con los últimos cuatro dígitos; no se guarda entero.
            'numero' => [
                Rule::requiredIf(fn () => $this->input('tipo') === 'tarjeta'),
                'nullable', 'string', 'min:13', 'max:23',
            ],
            'vencimiento' => [
                Rule::requiredIf(fn () => $this->input('tipo') === 'tarjeta'),
                'nullable', 'string', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/',
            ],
            'etiqueta' => ['nullable', 'string', 'max:60'],
            'predeterminado' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'tipo' => 'tipo de método',
            'titular' => 'titular',
            'numero' => 'número de tarjeta',
            'vencimiento' => 'fecha de vencimiento',
            'etiqueta' => 'etiqueta',
        ];
    }

    public function messages(): array
    {
        return [
            'vencimiento.regex' => 'La fecha de vencimiento debe tener el formato MM/AA.',
            'numero.min' => 'El número de tarjeta no parece completo.',
        ];
    }
}
