<?php

namespace App\Http\Controllers;

use App\Enums\EstadoComercial;
use App\Enums\Rol;
use App\Models\Modulo;
use App\Models\Programa;
use App\Services\MisClasesService;
use App\Services\ResumenCatalogoService;
use Illuminate\View\View;

class PanelController extends Controller
{
    public function index(ResumenCatalogoService $resumen, MisClasesService $clases): View
    {
        $usuario = auth()->user();

        return match ($usuario->rol) {
            Rol::Administrador => view('panel.administrador', [
                'oferta' => $resumen->ofertaVisibleParaCrm(),
                'avisos' => $resumen->avisos(),
                'totales' => $resumen->totales(),
            ]),
            Rol::Docente => view('panel.docente', [
                'grupos' => $clases->proximos($clases->gruposDelDocente($usuario), fn ($grupo) => $grupo),
            ]),
            Rol::Estudiante => view('panel.estudiante', [
                'inscripciones' => $clases->proximos($clases->inscripcionesDelEstudiante($usuario), fn ($inscripcion) => $inscripcion->grupo),
            ]),
        };
    }

    public function catalogo(): View
    {
        $programas = Programa::with(['modulos' => fn ($q) => $q->where('modulos.estado_comercial', EstadoComercial::Abierto->value)])
            ->where('estado_comercial', EstadoComercial::Abierto->value)
            ->orderBy('nombre')
            ->get();

        $modulos = Modulo::where('estado_comercial', EstadoComercial::Abierto->value)
            ->orderBy('nombre')
            ->get();

        return view('catalogo.index', compact('programas', 'modulos'));
    }
}
