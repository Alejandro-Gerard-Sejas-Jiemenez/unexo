<?php

namespace App\Http\Controllers;

use App\Models\Grupo;
use App\Services\MisClasesService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MisGruposController extends Controller
{
    public function index(Request $request, MisClasesService $clases): View
    {
        $todos = $clases->gruposDelDocente($request->user());
        $grupoDe = fn ($grupo) => $grupo;
        $fase = $request->string('fase')->toString();
        $busqueda = $request->string('q')->toString();

        $segunBusqueda = $clases->filtrar($todos, $grupoDe, null, $busqueda);

        return view('mis-grupos.index', [
            'grupos' => $clases->filtrar($todos, $grupoDe, $fase, $busqueda),
            'conteo' => $clases->conteoPorFase($segunBusqueda, $grupoDe),
            'total' => $segunBusqueda->count(),
            'hayGrupos' => $todos->isNotEmpty(),
            'fase' => $fase,
            'busqueda' => $busqueda,
        ]);
    }

    public function show(Request $request, Grupo $grupo, MisClasesService $clases): View
    {
        abort_unless($grupo->docente_id === $request->user()->id, 403, 'Este grupo no está asignado a tu usuario.');

        $grupo->load(['modulo', 'cohorte.programa', 'aula']);

        return view('mis-grupos.show', [
            'grupo' => $grupo,
            'inscripciones' => $clases->estudiantesDelGrupo($grupo),
        ]);
    }
}
