<?php

namespace App\Http\Requests;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Reglas de validación de usuarios: una sola fuente para el panel Blade y para la API.
 * Si cambias una regla aquí, los dos extremos cambian juntos.
 */
class UsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->rol === Rol::Administrador;
    }

    /** El usuario que se está editando, o null si es un alta. */
    private function usuarioEditado(): ?User
    {
        $usuario = $this->route('usuario');

        return $usuario instanceof User ? $usuario : null;
    }

    public function rules(): array
    {
        $usuario = $this->usuarioEditado();

        return [
            'rol' => ['required', Rule::enum(Rol::class)],
            'nombres' => ['required', 'string', 'max:'.config('limites.usuarios.nombres')],
            'apellidos' => ['required', 'string', 'max:'.config('limites.usuarios.apellidos')],
            'documento_identidad' => ['nullable', 'string', 'max:'.config('limites.usuarios.documento_identidad'), Rule::unique('users', 'documento_identidad')->ignore($usuario)],
            'email' => ['required', 'email', 'max:'.config('limites.usuarios.email'), Rule::unique('users', 'email')->ignore($usuario)],
            'correo_institucional' => ['nullable', 'email', 'max:'.config('limites.usuarios.correo_institucional'), Rule::unique('users', 'correo_institucional')->ignore($usuario)],
            'telefono' => ['nullable', 'string', 'max:'.config('limites.usuarios.telefono')],
            'password' => [$usuario ? 'nullable' : 'required', 'string', 'min:8', 'max:'.config('limites.usuarios.password')],
            'foto_perfil_url' => ['nullable', 'string', 'max:255'],
            'foto_perfil' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:2048'],
        ];
    }

    public function attributes(): array
    {
        return [
            'rol' => 'rol',
            'nombres' => 'nombres',
            'apellidos' => 'apellidos',
            'documento_identidad' => 'documento de identidad',
            'email' => 'correo electrónico',
            'correo_institucional' => 'correo institucional',
            'telefono' => 'teléfono',
            'password' => 'contraseña',
            'foto_perfil_url' => 'foto de perfil',
            'foto_perfil' => 'foto de perfil',
        ];
    }

    public function datosParaGuardar(): array
    {
        $datos = $this->safe()->except('foto_perfil');

        if ($this->usuarioEditado() && empty($datos['password'])) {
            unset($datos['password']);
        }

        return $datos;
    }
}
