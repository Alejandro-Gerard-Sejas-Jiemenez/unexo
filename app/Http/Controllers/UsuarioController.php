<?php

namespace App\Http\Controllers;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(Request $request): View
    {
        $rol = $request->query('rol');
        $soloInactivos = $request->query('estado') === 'inactivo';
        $busqueda = trim((string) $request->query('q', ''));

        $usuarios = User::query()
            ->when(Rol::tryFrom((string) $rol), fn ($q, $r) => $q->where('rol', $r->value))
            ->when($soloInactivos, fn ($q) => $q->where('estado', 'inactivo'))
            ->when($busqueda !== '', fn ($q) => $q->where(fn ($b) => $b
                ->where('nombres', 'like', "%{$busqueda}%")
                ->orWhere('apellidos', 'like', "%{$busqueda}%")
                ->orWhere('email', 'like', "%{$busqueda}%")
                ->orWhere('correo_institucional', 'like', "%{$busqueda}%")))
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->paginate(config('limites.por_pagina'))->withQueryString();

        return view('usuarios.index', ['usuarios' => $usuarios, 'rolFiltro' => $rol, 'soloInactivos' => $soloInactivos, 'busqueda' => $busqueda]);
    }

    public function create(): View
    {
        return view('usuarios.form', ['usuario' => new User(['estado' => 'activo'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglas(null));
        User::create($datos);

        return redirect()->route('usuarios.index')->with('ok', 'Usuario creado correctamente.');
    }

    public function edit(User $usuario): View
    {
        return view('usuarios.form', compact('usuario'));
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $datos = $request->validate($this->reglas($usuario));

        if (empty($datos['password'])) {
            unset($datos['password']);
        }

        $usuario->update($datos);

        return redirect()->route('usuarios.index')->with('ok', 'Usuario actualizado correctamente.');
    }

    public function cambiarEstado(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->is($request->user())) {
            return back()->withErrors(['usuario' => 'No puedes desactivar tu propia cuenta.']);
        }

        $usuario->update(['estado' => $usuario->estaActivo() ? 'inactivo' : 'activo']);

        return back()->with('ok', 'Estado actualizado.');
    }

    private function reglas(?User $usuario): array
    {
        return [
            'rol' => ['required', Rule::enum(Rol::class)],
            'nombres' => ['required', 'string', 'max:'.config('limites.usuarios.nombres')],
            'apellidos' => ['required', 'string', 'max:'.config('limites.usuarios.apellidos')],
            'documento_identidad' => ['nullable', 'string', 'max:'.config('limites.usuarios.documento_identidad'), Rule::unique('users', 'documento_identidad')->ignore($usuario)],
            'email' => ['required', 'email', 'max:'.config('limites.usuarios.email'), Rule::unique('users', 'email')->ignore($usuario)],
            'correo_institucional' => ['nullable', 'email', 'max:'.config('limites.usuarios.correo_institucional'), Rule::unique('users', 'correo_institucional')->ignore($usuario)],
            'telefono' => ['nullable', 'string', 'max:'.config('limites.usuarios.telefono')],
            'password' => [$usuario ? 'nullable' : 'required', 'string', 'min:8', 'max:'.config('limites.usuarios.password')],
        ];
    }
}
