<?php

namespace App\Services;

use App\Enums\EstadoMatricula;
use App\Enums\OrigenMatricula;
use App\Enums\Rol;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\InscripcionGrupo;
use App\Models\Matricula;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MatriculaService
{
    /** Inscribe a un estudiante en la cohorte completa: una fila por cada grupo de la cohorte. */
    public function inscribirEnCohorte(User $estudiante, Cohorte $cohorte, OrigenMatricula $origen = OrigenMatricula::Manual): Matricula
    {
        return DB::transaction(function () use ($estudiante, $cohorte, $origen) {
            $matricula = Matricula::create([
                'estudiante_id' => $estudiante->id,
                'cohorte_id' => $cohorte->id,
                'estado' => EstadoMatricula::Pendiente,
                'origen' => $origen,
                'id_externo_crm' => $origen === OrigenMatricula::Crm ? $estudiante->id_externo_crm : null,
                'fecha_matricula' => today(),
            ]);

            foreach ($cohorte->grupos as $grupo) {
                InscripcionGrupo::create(['matricula_id' => $matricula->id, 'grupo_id' => $grupo->id]);
            }

            return $matricula;
        });
    }

    /** Inscribe a un estudiante en un grupo de módulo suelto (sin cohorte). */
    public function inscribirEnGrupoSuelto(User $estudiante, Grupo $grupo, OrigenMatricula $origen = OrigenMatricula::Manual): Matricula
    {
        return DB::transaction(function () use ($estudiante, $grupo, $origen) {
            $matricula = Matricula::create([
                'estudiante_id' => $estudiante->id,
                'cohorte_id' => null,
                'estado' => EstadoMatricula::Pendiente,
                'origen' => $origen,
                'id_externo_crm' => $origen === OrigenMatricula::Crm ? $estudiante->id_externo_crm : null,
                'fecha_matricula' => today(),
            ]);

            InscripcionGrupo::create(['matricula_id' => $matricula->id, 'grupo_id' => $grupo->id]);

            return $matricula;
        });
    }

    public function yaInscritoEnCohorte(User $estudiante, Cohorte $cohorte): bool
    {
        return Matricula::where('estudiante_id', $estudiante->id)
            ->where('cohorte_id', $cohorte->id)
            ->exists();
    }

    public function yaInscritoEnGrupo(User $estudiante, Grupo $grupo): bool
    {
        return InscripcionGrupo::where('grupo_id', $grupo->id)
            ->whereHas('matricula', fn ($m) => $m->where('estudiante_id', $estudiante->id))
            ->exists();
    }

    /**
     * Cambia el estado de la matrícula. Una matrícula retirada queda como historial y no se modifica (RNF2).
     *
     * @throws ValidationException
     */
    public function cambiarEstado(Matricula $matricula, EstadoMatricula $nuevo): Matricula
    {
        if ($matricula->estado === EstadoMatricula::Retirado) {
            throw ValidationException::withMessages(['estado' => 'Una matrícula retirada se conserva como historial y no se puede modificar.']);
        }
        if ($matricula->estado === $nuevo) {
            throw ValidationException::withMessages(['estado' => 'La matrícula ya está en ese estado.']);
        }

        $matricula->update([
            'estado' => $nuevo,
            'fecha_retiro' => $nuevo === EstadoMatricula::Retirado ? today() : null,
        ]);

        return $matricula;
    }

    /**
     * Recibe un alumno convertido del CRM (R4). Es idempotente: se busca por id_externo_crm (o por correo,
     * para enlazar un alumno cargado a mano) y solo se crea la matrícula si todavía no existe.
     *
     * @param  array{id_externo_crm: string, nombres: string, apellidos: string, email: string, documento_identidad?: ?string, telefono?: ?string}  $datos
     * @return array{0: User, 1: ?Matricula, 2: bool}  alumno, matrícula nueva (si la hubo) y si el alumno se creó
     */
    public function recibirConvertido(array $datos, ?Cohorte $cohorte = null, ?Grupo $grupo = null): array
    {
        return DB::transaction(function () use ($datos, $cohorte, $grupo) {
            $estudiante = $this->buscarConvertido($datos['id_externo_crm'], $datos['email']);
            $creado = $estudiante === null;

            $campos = [
                'nombres' => $datos['nombres'],
                'apellidos' => $datos['apellidos'],
                'email' => $datos['email'],
                'documento_identidad' => $datos['documento_identidad'] ?? null,
                'telefono' => $datos['telefono'] ?? null,
                'id_externo_crm' => $datos['id_externo_crm'],
            ];

            if ($creado) {
                $estudiante = User::create($campos + [
                    'rol' => Rol::Estudiante,
                    'password' => Str::random(40), // entra con el acceso que le dé la administración
                    'estado' => 'activo',
                ]);
            } else {
                $estudiante->update(array_filter($campos, fn ($v) => $v !== null));
            }

            $matricula = null;
            if ($cohorte && ! $this->yaInscritoEnCohorte($estudiante, $cohorte)) {
                $matricula = $this->inscribirEnCohorte($estudiante, $cohorte, OrigenMatricula::Crm);
            } elseif ($grupo && ! $this->yaInscritoEnGrupo($estudiante, $grupo)) {
                $matricula = $this->inscribirEnGrupoSuelto($estudiante, $grupo, OrigenMatricula::Crm);
            }

            return [$estudiante, $matricula, $creado];
        });
    }

    /** El alumno ya existente que corresponde a este convertido, si lo hay. */
    public function buscarConvertido(string $idExterno, string $email): ?User
    {
        return User::where('rol', Rol::Estudiante->value)
            ->where(fn ($q) => $q->where('id_externo_crm', $idExterno)->orWhere('email', $email))
            ->first();
    }
}
