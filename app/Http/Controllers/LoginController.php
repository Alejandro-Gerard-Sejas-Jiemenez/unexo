<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'usuario' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $clave = Str::lower($datos['usuario']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($clave, 5)) {
            $segundos = RateLimiter::availableIn($clave);

            return back()->withErrors(['usuario' => "Demasiados intentos. Vuelve a intentarlo en {$segundos} segundos."])->onlyInput('usuario');
        }

        $usuario = User::where('email', $datos['usuario'])
            ->orWhere('correo_institucional', $datos['usuario'])
            ->first();

        if (! $usuario || ! Hash::check($datos['password'], $usuario->password)) {
            RateLimiter::hit($clave, 60);

            return back()->withErrors(['usuario' => 'El correo o la contraseña no son correctos. Revisa los datos e inténtalo de nuevo.'])->onlyInput('usuario');
        }

        if (! $usuario->estaActivo()) {
            return back()->withErrors(['usuario' => 'Tu cuenta está inactiva. Contacta al administrador.'])->onlyInput('usuario');
        }

        RateLimiter::clear($clave);
        Auth::login($usuario, $request->boolean('recordar'));
        $request->session()->regenerate();

        return redirect()->intended(route('panel'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
