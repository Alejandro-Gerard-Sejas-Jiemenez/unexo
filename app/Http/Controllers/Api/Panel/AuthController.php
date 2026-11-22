<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Http\Resources\UsuarioResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Autenticación de la SPA. Usa la sesión (Sanctum stateful), no tokens:
 * la cookie va en HttpOnly y el token nunca pasa por JavaScript.
 */
class AuthController extends Controller
{
    private const INTENTOS_MAXIMOS = 5;

    private const BLOQUEO_SEGUNDOS = 60;

    public function login(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'usuario' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [], ['usuario' => 'correo', 'password' => 'contraseña']);

        $clave = Str::lower($datos['usuario']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($clave, self::INTENTOS_MAXIMOS)) {
            $segundos = RateLimiter::availableIn($clave);

            throw ValidationException::withMessages([
                'usuario' => "Demasiados intentos. Vuelve a intentarlo en {$segundos} segundos.",
            ]);
        }

        $usuario = User::where('email', $datos['usuario'])
            ->orWhere('correo_institucional', $datos['usuario'])
            ->first();

        if (! $usuario || ! Hash::check($datos['password'], $usuario->password)) {
            RateLimiter::hit($clave, self::BLOQUEO_SEGUNDOS);

            throw ValidationException::withMessages([
                'usuario' => 'El correo o la contraseña no son correctos. Revisa los datos e inténtalo de nuevo.',
            ]);
        }

        if (! $usuario->estaActivo()) {
            throw ValidationException::withMessages([
                'usuario' => 'Tu cuenta está inactiva. Contacta al administrador.',
            ]);
        }

        RateLimiter::clear($clave);
        Auth::login($usuario, $request->boolean('recordar'));
        $request->session()->regenerate();

        return response()->json(['usuario' => new UsuarioResource($usuario)]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['mensaje' => 'Sesión cerrada.']);
    }

    /** Quién está autenticado. La SPA lo llama al arrancar para restaurar la sesión. */
    public function me(Request $request): JsonResponse
    {
        return response()->json(['usuario' => new UsuarioResource($request->user())]);
    }
}
