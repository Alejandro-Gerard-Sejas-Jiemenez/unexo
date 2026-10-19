<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MatriculaResource;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\User;
use App\Services\MatriculaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** El CRM envía aquí los alumnos convertidos (CU21 / R4). */
class EstudianteConvertidoController extends Controller
{
    public function store(Request $request, MatriculaService $servicio): JsonResponse
    {
        $existente = $servicio->buscarConvertido(
            (string) $request->input('id_externo_crm'),
            (string) $request->input('email'),
        );

        $datos = $request->validate([
            'id_externo_crm' => ['required', 'string', 'max:60'],
            'nombres' => ['required', 'string', 'max:'.config('limites.usuarios.nombres')],
            'apellidos' => ['required', 'string', 'max:'.config('limites.usuarios.apellidos')],
            'email' => ['required', 'email', 'max:'.config('limites.usuarios.email'),
                Rule::unique('users', 'email')->ignore($existente?->id)],
            'documento_identidad' => ['nullable', 'string', 'max:'.config('limites.usuarios.documento_identidad'),
                Rule::unique('users', 'documento_identidad')->ignore($existente?->id)],
            'telefono' => ['nullable', 'string', 'max:'.config('limites.usuarios.telefono')],
            'cohorte_id' => ['nullable', 'uuid', 'exists:cohortes,id', 'prohibits:grupo_id'],
            'grupo_id' => ['nullable', 'uuid', Rule::exists('grupos', 'id')->whereNull('cohorte_id')],
        ]);

        if ($existente && $existente->id_externo_crm !== null && $existente->id_externo_crm !== $datos['id_externo_crm']) {
            return response()->json(['message' => 'El correo ya pertenece a otro alumno del CRM.'], 409);
        }

        [$estudiante, $matricula, $creado] = $servicio->recibirConvertido(
            $datos,
            isset($datos['cohorte_id']) ? Cohorte::with('grupos')->find($datos['cohorte_id']) : null,
            isset($datos['grupo_id']) ? Grupo::find($datos['grupo_id']) : null,
        );

        return response()->json([
            'estudiante' => $this->estudiante($estudiante),
            'matricula' => $matricula ? new MatriculaResource($matricula->load('estudiante', 'cohorte.programa', 'inscripciones.grupo.modulo', 'inscripciones.grupo.cohorte')) : null,
        ], $creado ? 201 : 200);
    }

    private function estudiante(User $u): array
    {
        return [
            'id' => $u->id,
            'id_externo_crm' => $u->id_externo_crm,
            'nombres' => $u->nombres,
            'apellidos' => $u->apellidos,
            'email' => $u->email,
        ];
    }
}
