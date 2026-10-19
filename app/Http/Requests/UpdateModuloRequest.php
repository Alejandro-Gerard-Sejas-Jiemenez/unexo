<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateModuloRequest extends StoreModuloRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'codigo' => ['required', 'string', 'max:'.config('limites.modulos.codigo'), Rule::unique('modulos', 'codigo')->ignore($this->route('modulo'))],
        ]);
    }
}
