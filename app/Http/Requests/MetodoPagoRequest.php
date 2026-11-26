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
        $usaStripe = config('services.payments.driver') === 'stripe';

        return [
            'tipo' => ['required', Rule::in($usaStripe ? ['qr', 'transferencia'] : ['tarjeta', 'qr', 'transferencia'])],
            'titular' => ['required', 'string', 'max:120'],
            'numero' => [
                Rule::requiredIf(fn () => ! $usaStripe && $this->input('tipo') === 'tarjeta'),
                'nullable', 'string', 'min:13', 'max:23',
            ],
            'vencimiento' => [
                Rule::requiredIf(fn () => ! $usaStripe && $this->input('tipo') === 'tarjeta'),
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
            'tipo.in' => config('services.payments.driver') === 'stripe'
                ? 'Con Stripe activo, las tarjetas se ingresan solo en la página de Stripe.'
                : 'El tipo de método no es válido.',
            'vencimiento.regex' => 'La fecha de vencimiento debe tener el formato MM/AA.',
            'numero.min' => 'El número de tarjeta no parece completo.',
        ];
    }
}
