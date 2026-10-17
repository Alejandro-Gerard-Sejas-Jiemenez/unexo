<?php

namespace App\Http\Requests\Concerns;

use App\Http\Requests\FiltrosReporteRequest;
use Illuminate\Validation\Validator;

/** Para los reportes con filtros `desde` y `hasta`: la comparación solo se hace si ambas pasaron su formato, para dar un único error. */
trait ValidaRangoDeFechas
{
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['desde', 'hasta'])) {
                return;
            }

            $desde = $this->input('desde');
            $hasta = $this->input('hasta');

            if ($desde && $hasta && $hasta < $desde) {
                $validator->errors()->add('hasta', FiltrosReporteRequest::MENSAJE_RANGO);
            }
        }];
    }
}
