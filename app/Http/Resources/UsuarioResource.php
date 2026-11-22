<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rol' => $this->rol->value,
            'rol_label' => $this->rol->label(),
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'nombre_completo' => $this->nombre_completo,
            'documento_identidad' => $this->documento_identidad,
            'email' => $this->email,
            'correo_institucional' => $this->correo_institucional,
            'telefono' => $this->telefono,
            'foto_perfil_url' => $this->foto_perfil_url,
            'estado' => $this->estado,
            'activo' => $this->estaActivo(),
        ];
    }
}
