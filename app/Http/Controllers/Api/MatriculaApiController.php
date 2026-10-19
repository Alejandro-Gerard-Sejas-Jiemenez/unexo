<?php

namespace App\Http\Controllers\Api;

use App\Enums\EstadoMatricula;
use App\Http\Controllers\Controller;
use App\Http\Resources\MatriculaResource;
use App\Models\Matricula;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/** Estado de las matrículas para el CRM (CU7). Solo lectura. */
class MatriculaApiController extends Controller
{
    private const RELACIONES = ['estudiante', 'cohorte.programa', 'inscripciones.grupo.modulo', 'inscripciones.grupo.cohorte'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $filtros = $request->validate([
            'id_externo_crm' => ['nullable', 'string', 'max:60'],
            'estado' => ['nullable', Rule::enum(EstadoMatricula::class)],
        ]);

        $matriculas = Matricula::with(self::RELACIONES)
            ->when($filtros['estado'] ?? null, fn ($q, $e) => $q->where('estado', $e))
            ->when($filtros['id_externo_crm'] ?? null, fn ($q, $id) => $q->whereHas(
                'estudiante', fn ($u) => $u->where('id_externo_crm', $id)
            ))
            ->orderByDesc('fecha_matricula')
            ->paginate(config('limites.por_pagina'))->withQueryString();

        return MatriculaResource::collection($matriculas);
    }

    public function show(Matricula $matricula): MatriculaResource
    {
        return new MatriculaResource($matricula->load(self::RELACIONES));
    }
}
