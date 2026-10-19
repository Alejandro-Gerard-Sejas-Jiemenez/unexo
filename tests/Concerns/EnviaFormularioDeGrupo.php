<?php

namespace Tests\Concerns;

use App\Enums\EstadoPlanificacion;
use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\HorarioGrupo;
use App\Models\Modulo;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Testing\TestResponse;

/** Apoyo para probar por HTTP el alta y la edición de grupos (spec 009, contracts/formularios.md sección 3). */
trait EnviaFormularioDeGrupo
{
    private int $secuenciaFormulario = 0;

    private ?User $adminFormulario = null;

    private ?User $docenteFormulario = null;

    protected function admin(): User
    {
        return $this->adminFormulario ??= User::factory()->create(['rol' => Rol::Administrador]);
    }

    protected function docenteTitular(): User
    {
        return $this->docenteFormulario ??= User::factory()->create(['rol' => Rol::Docente, 'estado' => 'activo']);
    }

    /** @return array{dia_semana: int, hora_inicio: string, hora_fin: string} */
    protected function horario(int $dia = 1, string $inicio = '19:00', string $fin = '21:00'): array
    {
        return ['dia_semana' => $dia, 'hora_inicio' => $inicio, 'hora_fin' => $fin];
    }

    /** Crea un módulo; si hay edición, lo agrega al programa de esa edición. */
    protected function moduloParaGrupo(?Cohorte $edicion = null): Modulo
    {
        $n = ++$this->secuenciaFormulario;
        $modulo = Modulo::create(['codigo' => "DEMO-F$n", 'nombre' => "DEMO Módulo F$n"]);

        $edicion?->programa->modulos()->attach($modulo->id, ['orden' => $n]);

        return $modulo;
    }

    /**
     * Datos válidos de alta (virtual, programado, un horario). Suelto lleva fechas; de edición no.
     *
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    protected function datosGrupo(Modulo $modulo, ?Cohorte $edicion = null, array $cambios = []): array
    {
        $datos = [
            'modulo_id' => $modulo->id,
            'docente_id' => $this->docenteTitular()->id,
            'modalidad' => Modalidad::Virtual->value,
            'estado' => EstadoPlanificacion::Programado->value,
            'horarios' => [$this->horario()],
        ];

        if ($edicion) {
            $datos['cohorte_id'] = $edicion->id;
        } else {
            $datos += ['fecha_inicio' => '2026-04-01', 'fecha_fin' => '2026-04-30'];
        }

        return array_merge($datos, $cambios);
    }

    /**
     * Datos de edición que reenvían lo que el grupo ya tiene (como el formulario reabierto).
     *
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    protected function datosDe(Grupo $grupo, array $cambios = []): array
    {
        $grupo = $grupo->fresh(['horarios']);

        $datos = [
            'modulo_id' => $grupo->modulo_id,
            'cohorte_id' => $grupo->cohorte_id ?? '',
            'docente_id' => $grupo->docente_id,
            'modalidad' => $grupo->modalidad->value,
            'estado' => $grupo->estado->value,
            'horarios' => $grupo->horarios->isEmpty()
                ? [$this->horario()]
                : $grupo->horarios->map(fn (HorarioGrupo $h) => $this->horario($h->dia_semana->value, substr($h->hora_inicio, 0, 5), substr($h->hora_fin, 0, 5)))->all(),
        ];

        if (! $grupo->cohorte_id) {
            $datos['fecha_inicio'] = $grupo->fecha_inicio->toDateString();
            $datos['fecha_fin'] = $grupo->fecha_fin->toDateString();
        }

        return array_merge($datos, $cambios);
    }

    /** Grupo ya existente con nombre "Grupo N" y su número (como lo dejaría el sistema). */
    protected function grupoNumerado(Modulo $modulo, ?Cohorte $edicion, int $numero, array $atributos = []): Grupo
    {
        $base = [
            'modulo_id' => $modulo->id,
            'docente_id' => $this->docenteTitular()->id,
            'nombre' => "Grupo $numero",
            'numero' => $numero,
            'estado' => EstadoPlanificacion::Programado,
            'modalidad' => Modalidad::Virtual,
        ];

        $base += $edicion
            ? ['cohorte_id' => $edicion->id]
            : ['fecha_inicio' => '2026-03-01', 'fecha_fin' => '2026-03-30'];

        $grupo = Grupo::create($atributos + $base);
        HorarioGrupo::create(['grupo_id' => $grupo->id, 'dia_semana' => 1, 'hora_inicio' => '19:00', 'hora_fin' => '21:00']);

        return $grupo;
    }

    /** @param  array<string, mixed>  $datos */
    protected function guardarGrupo(array $datos): TestResponse
    {
        return $this->actingAs($this->admin())->post(route('grupos.store'), $datos);
    }

    /** @param  array<string, mixed>  $datos */
    protected function actualizarGrupo(Grupo $grupo, array $datos): TestResponse
    {
        return $this->actingAs($this->admin())->put(route('grupos.update', $grupo), $datos);
    }

    /** Nombres de los grupos de un contexto (módulo y edición, o sueltos del módulo), ordenados. */
    protected function nombresEnContexto(Modulo $modulo, ?Cohorte $edicion): array
    {
        return Grupo::where('modulo_id', $modulo->id)
            ->when($edicion, fn ($q) => $q->where('cohorte_id', $edicion->id), fn ($q) => $q->whereNull('cohorte_id'))
            ->orderBy('numero')
            ->pluck('nombre')
            ->all();
    }

    protected function xpath(string $html): DOMXPath
    {
        $documento = new DOMDocument;
        @$documento->loadHTML('<?xml encoding="utf-8" ?>'.$html);

        return new DOMXPath($documento);
    }
}
