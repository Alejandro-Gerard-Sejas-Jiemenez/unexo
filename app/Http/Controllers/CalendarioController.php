<?php

namespace App\Http\Controllers;

use App\Services\CalendarioService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Agenda de clases en el panel Blade. La lógica vive en CalendarioService,
 * compartida con la API que consume la SPA.
 */
class CalendarioController extends Controller
{
    public function index(Request $request, CalendarioService $calendario): View
    {
        $grupos = $calendario->gruposDe($request->user());
        $mes = $calendario->mesSolicitado($request->query('mes'));
        $eventos = $calendario->eventosDeGrupos($grupos);

        return view('calendario.index', [
            'mes' => $mes,
            'semanas' => $calendario->semanas($mes, $eventos),
            'agenda' => $calendario->agendaDelMes($eventos, $mes),
            'mesAnterior' => $mes->subMonth()->format('Y-m'),
            'mesSiguiente' => $mes->addMonth()->format('Y-m'),
        ]);
    }
}
