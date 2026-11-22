<?php

namespace App\Http\Resources;

use App\Models\Bitacora;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Bitacora
 */
class BitacoraResource extends JsonResource
{
    private const ETIQUETAS = [
        'login_exitoso' => 'Inicio de sesión',
        'login_fallido' => 'Intento fallido',
        'logout' => 'Cierre de sesión',
        'acceso_recurso' => 'Acceso a recurso',
    ];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'evento' => $this->evento,
            'evento_label' => self::ETIQUETAS[$this->evento] ?? $this->evento,
            'usuario' => $this->usuario?->nombre_completo ?? $this->identificador ?? 'Anónimo',
            'recurso' => $this->recurso,
            'metodo_http' => $this->metodo_http,
            'codigo_respuesta' => $this->codigo_respuesta,
            'ip' => $this->ip,
            'fecha' => $this->created_at?->format('d/m/Y H:i:s'),
        ];
    }
}
