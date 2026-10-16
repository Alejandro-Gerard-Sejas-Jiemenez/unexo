<?php

namespace App\Http\Controllers;

use App\Models\ClienteApi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** CU28: el administrador genera y revoca el token que usa el CRM. */
class ClienteApiController extends Controller
{
    public function index(): View
    {
        return view('clientes-api.index', [
            'clientes' => ClienteApi::withCount('tokens')->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:80', 'unique:api_clientes,nombre'],
        ]);

        $cliente = ClienteApi::create($datos);
        $token = $cliente->createToken('crm')->plainTextToken;

        // El token solo se muestra esta vez; en la base queda su hash.
        return redirect()->route('clientes-api.index')
            ->with('ok', "Cliente «{$cliente->nombre}» creado.")
            ->with('token', $token);
    }

    public function destroy(ClienteApi $cliente): RedirectResponse
    {
        $cliente->tokens()->delete();
        $cliente->delete();

        return redirect()->route('clientes-api.index')->with('ok', 'Acceso revocado.');
    }
}
