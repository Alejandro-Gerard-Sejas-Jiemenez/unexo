<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsuarioRequest;
use App\Models\User;
use App\Services\UsuarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Panel Blade de usuarios. Comparte las reglas de validación (UsuarioRequest) y la
 * consulta con filtros (UsuarioService) con la API que consume la SPA de React,
 * para que los dos extremos validen y filtren igual.
 */
class UsuarioController extends Controller
{
    public function __construct(private readonly UsuarioService $usuarios) {}

    public function index(Request $request): View
    {
        $filtros = $request->only(['rol', 'estado', 'q']);
        $usuarios = $this->usuarios->listar($filtros)->withQueryString();

        return view('usuarios.index', [
            'usuarios' => $usuarios,
            'rolFiltro' => $filtros['rol'] ?? null,
            'soloInactivos' => ($filtros['estado'] ?? null) === 'inactivo',
            'busqueda' => trim((string) ($filtros['q'] ?? '')),
        ]);
    }

    public function create(): View
    {
        return view('usuarios.form', ['usuario' => new User(['estado' => 'activo'])]);
    }

    public function store(UsuarioRequest $request): RedirectResponse
    {
        User::create($request->datosParaGuardar());

        return redirect()->route('usuarios.index')->with('ok', 'Usuario creado correctamente.');
    }

    public function edit(User $usuario): View
    {
        return view('usuarios.form', compact('usuario'));
    }

    public function update(UsuarioRequest $request, User $usuario): RedirectResponse
    {
        $usuario->update($request->datosParaGuardar());

        return redirect()->route('usuarios.index')->with('ok', 'Usuario actualizado correctamente.');
    }

    public function cambiarEstado(Request $request, User $usuario): RedirectResponse
    {
        $error = $this->usuarios->alternarEstado($usuario, $request->user());

        if ($error !== null) {
            return back()->withErrors(['usuario' => $error]);
        }

        return back()->with('ok', 'Estado actualizado.');
    }
}
