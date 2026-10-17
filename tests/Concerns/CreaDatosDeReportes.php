<?php

namespace Tests\Concerns;

use App\Enums\EstadoComercial;
use App\Enums\EstadoMatricula;
use App\Enums\EstadoPlanificacion;
use App\Enums\OrigenMatricula;
use App\Enums\Rol;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\InscripcionGrupo;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\User;
use App\Support\Reporte;

trait CreaDatosDeReportes
{
    private int $secuencia = 0;

    private function siguiente(): int
    {
        return ++$this->secuencia;
    }

    protected function admin(): User
    {
        return User::factory()->create(['rol' => Rol::Administrador]);
    }

    protected function docente(array $atributos = []): User
    {
        return User::factory()->create($atributos + ['rol' => Rol::Docente]);
    }

    protected function programa(EstadoComercial $estado = EstadoComercial::Abierto, ?string $nombre = null): Programa
    {
        $n = $this->siguiente();

        return Programa::create([
            'codigo' => "DEMO-P$n",
            'nombre' => $nombre ?? "DEMO Programa $n",
            'version' => '1.0',
            'estado_comercial' => $estado,
        ]);
    }

    protected function modulo(EstadoComercial $estado = EstadoComercial::Abierto, ?string $nombre = null): Modulo
    {
        $n = $this->siguiente();

        return Modulo::create([
            'codigo' => "DEMO-M$n",
            'nombre' => $nombre ?? "DEMO Módulo $n",
            'estado_comercial' => $estado,
        ]);
    }

    protected function cohorte(?Programa $programa = null, ?string $nombre = null): Cohorte
    {
        $n = $this->siguiente();

        return Cohorte::create([
            'programa_id' => ($programa ?? $this->programa())->id,
            'nombre' => $nombre ?? "DEMO Cohorte $n",
            'fecha_inicio' => '2026-03-01',
            'fecha_fin' => '2026-08-30',
            'estado' => EstadoPlanificacion::Programado,
        ]);
    }

    protected function matricula(array $atributos = []): Matricula
    {
        $cohorte = $atributos['cohorte'] ?? null;
        unset($atributos['cohorte']);

        return Matricula::create($atributos + [
            'estudiante_id' => User::factory()->create()->id,
            'cohorte_id' => $cohorte?->id,
            'estado' => EstadoMatricula::Activo,
            'origen' => OrigenMatricula::Manual,
            'fecha_matricula' => '2026-03-10',
        ]);
    }

    protected function grupo(array $atributos = []): Grupo
    {
        $n = $this->siguiente();
        $cohorte = $atributos['cohorte'] ?? null;
        unset($atributos['cohorte']);

        return Grupo::create($atributos + [
            'modulo_id' => $this->modulo()->id,
            'cohorte_id' => $cohorte?->id,
            'docente_id' => $this->docente()->id,
            'nombre' => "DEMO Grupo $n",
            'fecha_inicio' => '2026-03-01',
            'fecha_fin' => '2026-03-30',
            'estado' => EstadoPlanificacion::Programado,
        ]);
    }

    protected function inscribir(Matricula $matricula, Grupo $grupo): InscripcionGrupo
    {
        return InscripcionGrupo::create(['matricula_id' => $matricula->id, 'grupo_id' => $grupo->id]);
    }

    /** Filas de una sección del reporte, buscada por su título. */
    protected function filasDe(Reporte $reporte, string $titulo): array
    {
        foreach ($reporte->secciones as $seccion) {
            if ($seccion['titulo'] === $titulo) {
                return $seccion['filas'];
            }
        }

        $this->fail("El reporte no tiene la sección «{$titulo}».");
    }

    protected function totalDe(Reporte $reporte, string $etiqueta): int
    {
        foreach ($reporte->totales as $total) {
            if ($total['etiqueta'] === $etiqueta) {
                return $total['valor'];
            }
        }

        $this->fail("El reporte no tiene el total «{$etiqueta}».");
    }

    /** Número que muestra la tarjeta de un total en la pantalla (RF-30), o null si no hay tarjeta con ese nombre. */
    protected function numeroDeTarjeta(string $html, string $etiqueta): ?string
    {
        $patron = '#<dd[^>]*data-total="'.preg_quote(e($etiqueta), '#').'"[^>]*>\s*([^<]*?)\s*</dd>#u';

        return preg_match($patron, $html, $coincidencia) ? $coincidencia[1] : null;
    }
}
