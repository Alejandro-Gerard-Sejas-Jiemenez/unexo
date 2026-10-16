<?php

namespace App\Http\Controllers;

use App\Enums\EstadoComprobante;
use App\Enums\EstadoMatricula;
use App\Enums\Rol;
use App\Models\Comprobante;
use App\Models\Matricula;
use App\Services\MatriculaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** CU31 (el estudiante sube su comprobante) y CU32 (el administrador lo aprueba o rechaza). */
class ComprobanteController extends Controller
{
    public function store(Request $request, Matricula $matricula): RedirectResponse
    {
        abort_unless($matricula->estudiante_id === $request->user()->id, 403);

        if ($matricula->estado !== EstadoMatricula::Pendiente) {
            throw ValidationException::withMessages(['archivo' => 'Solo se sube comprobante a una matrícula pendiente de pago.']);
        }

        $request->validate([
            'archivo' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ]);

        $matricula->comprobantes()->create([
            'archivo_ruta' => $request->file('archivo')->store('comprobantes'),
            'estado' => EstadoComprobante::Pendiente,
            'subido_por' => $request->user()->id,
        ]);

        return back()->with('ok', 'Comprobante enviado. La administración lo revisará.');
    }

    public function ver(Request $request, Comprobante $comprobante): StreamedResponse
    {
        $usuario = $request->user();
        abort_unless(
            $usuario->rol === Rol::Administrador || $comprobante->matricula->estudiante_id === $usuario->id,
            403
        );

        return Storage::response($comprobante->archivo_ruta);
    }

    public function aprobar(Request $request, Comprobante $comprobante, MatriculaService $servicio): RedirectResponse
    {
        $this->resolver($request, $comprobante, EstadoComprobante::Aprobado);

        if ($comprobante->matricula->estado === EstadoMatricula::Pendiente) {
            $servicio->cambiarEstado($comprobante->matricula, EstadoMatricula::Activo);
        }

        return back()->with('ok', 'Pago aprobado: la matrícula quedó activa.');
    }

    public function rechazar(Request $request, Comprobante $comprobante): RedirectResponse
    {
        $this->resolver($request, $comprobante, EstadoComprobante::Rechazado);

        return back()->with('ok', 'Comprobante rechazado.');
    }

    private function resolver(Request $request, Comprobante $comprobante, EstadoComprobante $estado): void
    {
        if ($comprobante->estado !== EstadoComprobante::Pendiente) {
            throw ValidationException::withMessages(['comprobante' => 'Este comprobante ya fue revisado.']);
        }

        $comprobante->update([
            'estado' => $estado,
            'verificado_por' => $request->user()->id,
            'fecha_verificacion' => now(),
        ]);
    }
}
