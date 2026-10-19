<?php

namespace App\Http\Controllers;

use App\Enums\EstadoMatricula;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Services\MisClasesService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MisCursosController extends Controller
{
    public function index(Request $request, MisClasesService $clases): View
    {
        $todas = $clases->inscripcionesDelEstudiante($request->user());
        $grupoDe = fn ($inscripcion) => $inscripcion->grupo;
        $fase = $request->string('fase')->toString();
        $busqueda = $request->string('q')->toString();

        $segunBusqueda = $clases->filtrar($todas, $grupoDe, null, $busqueda);

        return view('mis-cursos.index', [
            'inscripciones' => $clases->filtrar($todas, $grupoDe, $fase, $busqueda),
            'conteo' => $clases->conteoPorFase($segunBusqueda, $grupoDe),
            'total' => $segunBusqueda->count(),
            'hayInscripciones' => $todas->isNotEmpty(),
            'pendientes' => Matricula::where('estudiante_id', $request->user()->id)
                ->where('estado', EstadoMatricula::Pendiente->value)
                ->with(['cohorte.programa', 'comprobantes', 'inscripciones.grupo.modulo'])
                ->get(),
            'fase' => $fase,
            'busqueda' => $busqueda,
        ]);
    }

    public function show(Request $request, Grupo $grupo, MisClasesService $clases): View
    {
        abort_unless($clases->estudianteTieneAcceso($request->user(), $grupo), 403, 'No tienes acceso a este grupo.');

        $grupo->load(['modulo', 'docente', 'aula', 'cohorte.programa', 'horarios']);

        return view('mis-cursos.show', ['grupo' => $grupo]);
    }
}
