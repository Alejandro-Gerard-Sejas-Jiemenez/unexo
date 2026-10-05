<?php

namespace App\Http\Controllers;

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
            'fase' => $fase,
            'busqueda' => $busqueda,
        ]);
    }
}
