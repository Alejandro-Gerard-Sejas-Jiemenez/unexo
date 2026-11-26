<?php

namespace App\Console\Commands;

use App\Services\SaludService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NexoSalud extends Command
{
    protected $signature = 'nexo:salud {--reparar : Crea lo que falte (esquema, tablas, datos iniciales) tras pedir confirmación}';

    protected $description = 'Diagnostica el sistema y, con --reparar, restablece la base si fue borrada';

    public function handle(SaludService $salud): int
    {
        $resultado = $salud->diagnosticar();
        $this->mostrar($resultado);

        if (! $this->option('reparar')) {
            return $resultado['estado'] === 'falla' ? self::FAILURE : self::SUCCESS;
        }

        return $this->reparar($salud, $resultado);
    }

    private function reparar(SaludService $salud, array $resultado): int
    {
        $fallan = collect($resultado['chequeos'])->where('estado', 'falla')->pluck('clave')->all();

        // Sin conexión no se sabe si la base fue borrada o solo está inalcanzable: no se toca nada.
        if (in_array('base_de_datos', $fallan, true)) {
            $this->error('No hay conexión con la base de datos; no se repara nada. Revisa DB_HOST, DB_PASSWORD y que PostgreSQL esté activo.');

            return self::FAILURE;
        }

        $pasos = $this->pasos($fallan);
        if ($pasos === []) {
            $this->info('No hay nada que reparar en la base de datos.');

            return self::SUCCESS;
        }

        $this->line('Se ejecutará, sin borrar nada existente:');
        foreach ($pasos as $paso) {
            $this->line('  - '.$paso['texto']);
        }
        if (! $this->confirm('¿Continuar?')) {
            return self::FAILURE;
        }

        foreach ($pasos as $paso) {
            $this->line('> '.$paso['texto']);
            ($paso['accion'])();
        }

        $this->newLine();
        $despues = $salud->diagnosticar();
        $this->mostrar($despues);

        return $despues['estado'] === 'falla' ? self::FAILURE : self::SUCCESS;
    }

    /** @return list<array{texto: string, accion: callable}> */
    private function pasos(array $fallan): array
    {
        $pasos = [];

        if (in_array('esquema', $fallan, true)) {
            $esquema = trim(explode(',', (string) config('database.connections.'.config('database.default').'.search_path', 'public'))[0]);
            $pasos[] = [
                'texto' => "Crear el esquema «{$esquema}»",
                'accion' => fn () => DB::statement('create schema if not exists "'.str_replace('"', '""', $esquema).'"'),
            ];
        }

        $necesitaTablas = array_intersect($fallan, ['esquema', 'migraciones', 'tablas', 'datos_iniciales']) !== [];
        if ($necesitaTablas) {
            $pasos[] = ['texto' => 'Crear las tablas que falten (migrate)', 'accion' => fn () => $this->call('migrate', ['--force' => true])];
            // Los seeders usan firstOrCreate/updateOrCreate: repetirlos no duplica datos.
            $pasos[] = ['texto' => 'Cargar los datos iniciales (db:seed)', 'accion' => fn () => $this->call('db:seed', ['--force' => true])];
        }

        return $pasos;
    }

    private function mostrar(array $resultado): void
    {
        $iconos = ['ok' => 'OK    ', 'alerta' => 'ALERTA', 'falla' => 'FALLA ', 'omitido' => '  -   '];

        foreach ($resultado['chequeos'] as $c) {
            $linea = "[{$iconos[$c['estado']]}] {$c['titulo']}: {$c['mensaje']}";
            match ($c['estado']) {
                'falla' => $this->error($linea),
                'alerta' => $this->warn($linea),
                default => $this->line($linea),
            };
            if ($c['accion'] && in_array($c['estado'], ['falla', 'alerta'], true)) {
                $this->line("         Solución: {$c['accion']}");
            }
        }
        $this->newLine();
        $this->line('Estado general: '.strtoupper($resultado['estado']));
    }
}
