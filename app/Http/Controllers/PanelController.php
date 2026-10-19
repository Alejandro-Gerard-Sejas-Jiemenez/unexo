<?php

namespace App\Http\Controllers;

use App\Enums\EstadoComercial;
use App\Enums\EstadoPlanificacion;
use App\Enums\Rol;
use App\Models\Modulo;
use App\Models\Programa;
use App\Services\MisClasesService;
use App\Services\ResumenCatalogoService;
use Illuminate\Http\Request;
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

    public function catalogo(Request $request): View
    {
        $busqueda = trim((string) $request->query('q', ''));
        $tab = $request->query('tab', 'todos');

        $programas = Programa::visibleParaCrm()
            ->with([
                'modulos' => fn ($q) => $q->where('modulos.estado_comercial', EstadoComercial::Abierto->value),
                'cohortes' => fn ($q) => $q->whereIn('estado', [
                    EstadoPlanificacion::Programado->value,
                    EstadoPlanificacion::EnCurso->value,
                ])->orderBy('fecha_inicio'),
                'cohortes.grupos' => fn ($q) => $q->with(['modulo', 'docente', 'aula', 'cohorte']),
            ])
            ->when($busqueda !== '', fn ($q) => $q->where(fn ($b) => $b
                ->where('nombre', 'like', "%{$busqueda}%")
                ->orWhere('codigo', 'like', "%{$busqueda}%")
                ->orWhere('descripcion', 'like', "%{$busqueda}%")))
            ->orderBy('nombre')
            ->get();

        $modulos = Modulo::visibleParaCrm()
            ->with([
                'programas',
                'grupos' => fn ($q) => $q->whereNull('cohorte_id')
                    ->whereIn('estado', [
                        EstadoPlanificacion::Programado->value,
                        EstadoPlanificacion::EnCurso->value,
                    ])
                    ->with(['docente', 'aula'])
                    ->orderBy('fecha_inicio'),
            ])
            ->when($busqueda !== '', fn ($q) => $q->where(fn ($b) => $b
                ->where('nombre', 'like', "%{$busqueda}%")
                ->orWhere('codigo', 'like', "%{$busqueda}%")
                ->orWhere('descripcion', 'like', "%{$busqueda}%")
            ))
            ->orderBy('nombre')
            ->get();

        return view('catalogo.index', compact('programas', 'modulos', 'busqueda', 'tab'));
    }
}
