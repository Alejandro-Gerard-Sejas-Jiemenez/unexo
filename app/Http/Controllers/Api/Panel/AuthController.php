<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginPanelRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\User;
use App\Services\BitacoraService;
use App\Services\MenuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const INTENTOS_MAXIMOS = 5;

    private const BLOQUEO_SEGUNDOS = 60;

    public function __construct(
        private readonly BitacoraService $bitacora,
        private readonly MenuService $menu,
    ) {}

    public function login(LoginPanelRequest $request): JsonResponse
    {
        $datos = $request->validated();
        $clave = Str::lower($datos['usuario']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($clave, self::INTENTOS_MAXIMOS)) {
            $segundos = RateLimiter::availableIn($clave);
            $this->bitacora->loginFallido($request, $datos['usuario'], 'bloqueado_por_intentos');

            throw ValidationException::withMessages([
                'usuario' => "Demasiados intentos. Vuelve a intentarlo en {$segundos} segundos.",
            ]);
        }

        $usuario = User::where('email', $datos['usuario'])
            ->orWhere('correo_institucional', $datos['usuario'])
            ->first();

        if (! $usuario || ! Hash::check($datos['password'], $usuario->password)) {
            RateLimiter::hit($clave, self::BLOQUEO_SEGUNDOS);
            $this->bitacora->loginFallido($request, $datos['usuario'], 'credenciales_incorrectas');

            throw ValidationException::withMessages([
                'usuario' => 'El correo o la contraseña no son correctos. Revisa los datos e inténtalo de nuevo.',
            ]);
        }

        if (! $usuario->estaActivo()) {
            $this->bitacora->loginFallido($request, $datos['usuario'], 'cuenta_inactiva');

            throw ValidationException::withMessages([
                'usuario' => 'Tu cuenta está inactiva. Contacta al administrador.',
            ]);
        }

        RateLimiter::clear($clave);
        Auth::login($usuario, $request->boolean('recordar'));
        $request->session()->regenerate();
        $this->bitacora->loginExitoso($request, $usuario->id);

        return response()->json([
            'usuario' => new UsuarioResource($usuario),
            'menu' => $this->menu->paraSpa($usuario),
            'permisos' => $this->menu->permisosDe($usuario),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->bitacora->logout($request, $request->user()?->id);
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['mensaje' => 'Sesión cerrada.']);
    }

    public function me(Request $request): JsonResponse
    {
        $usuario = $request->user();

        return response()->json([
            'usuario' => new UsuarioResource($usuario),
            'menu' => $this->menu->paraSpa($usuario),
            'permisos' => $this->menu->permisosDe($usuario),
        ]);
    }
}
