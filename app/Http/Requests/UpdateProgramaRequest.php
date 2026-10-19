<?php

namespace App\Http\Requests;

use App\Models\Programa;

class UpdateProgramaRequest extends StoreProgramaRequest
{
    protected function programaActual(): ?Programa
    {
        return $this->route('programa');
    }
}
