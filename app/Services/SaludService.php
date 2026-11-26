<?php

namespace App\Services;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Diagnóstico del sistema: base de datos, migraciones, datos iniciales,
 * permisos de escritura, configuración y pagos. Solo lee, nunca modifica.
 */
class SaludService
{
    public const TABLAS_CLAVE = [
        'users', 'roles', 'permisos', 'menu_items', 'matriculas',
        'transacciones', 'bitacora', 'visitas_pagina', 'sessions', 'cache',
    ];

    private const EXTENSIONES = ['pdo_pgsql', 'mbstring', 'gd', 'intl', 'zip', 'fileinfo'];

    private bool $hayBase = false;

    /**
     * @return array{estado: string, critico_ok: bool, chequeos: list<array<string, mixed>>}
     */
    public function diagnosticar(): array
    {
        $chequeos = [
            $this->chequeo('base_de_datos', 'Conexión a la base de datos', true, fn () => $this->conexion()),
            $this->chequeo('esquema', 'Esquema de la base', true, fn () => $this->esquema(), true),
            $this->chequeo('migraciones', 'Migraciones aplicadas', true, fn () => $this->migraciones(), true),
            $this->chequeo('tablas', 'Tablas principales', true, fn () => $this->tablas(), true),
            $this->chequeo('datos_iniciales', 'Datos iniciales (roles y administrador)', true, fn () => $this->datosIniciales(), true),
            $this->chequeo('almacenamiento', 'Carpetas de escritura', false, fn () => $this->almacenamiento()),
            $this->chequeo('clave_app', 'Clave de la aplicación', true, fn () => $this->claveApp()),
            $this->chequeo('configuracion', 'Modo de producción', false, fn () => $this->configuracion()),
            $this->chequeo('extensiones', 'Extensiones de PHP', false, fn () => $this->extensiones()),
            $this->chequeo('pagos', 'Pasarela de pagos', false, fn () => $this->pagos()),
            $this->chequeo('mantenimiento', 'Modo mantenimiento', false, fn () => $this->mantenimiento()),
        ];

        $estados = array_column($chequeos, 'estado');
        $criticoFalla = collect($chequeos)->contains(fn ($c) => $c['critico'] && $c['estado'] === 'falla');

        return [
            'estado' => in_array('falla', $estados, true) ? 'falla' : (in_array('alerta', $estados, true) ? 'alerta' : 'ok'),
            'critico_ok' => ! $criticoFalla,
            'chequeos' => $chequeos,
        ];
    }

    /**
     * Cada chequeo se aísla: una excepción lo marca como falla sin frenar al resto.
     * Los que necesitan la base se omiten si la conexión ya falló.
     */
    private function chequeo(string $clave, string $titulo, bool $critico, callable $evaluar, bool $necesitaBase = false): array
    {
        $base = ['clave' => $clave, 'titulo' => $titulo, 'critico' => $critico];

        if ($necesitaBase && ! $this->hayBase) {
            return $base + ['estado' => 'omitido', 'mensaje' => 'No se pudo evaluar: no hay conexión con la base de datos.', 'accion' => null];
        }

        try {
            [$estado, $mensaje, $accion] = $evaluar() + [2 => null];
        } catch (Throwable $e) {
            [$estado, $mensaje, $accion] = ['falla', 'Error al comprobar: '.$e->getMessage(), null];
        }

        return $base + ['estado' => $estado, 'mensaje' => $mensaje, 'accion' => $accion];
    }

    private function conexion(): array
    {
        $inicio = microtime(true);
        try {
            DB::select('select 1');
        } catch (Throwable $e) {
            $this->hayBase = false;

            return ['falla', 'No se puede conectar a la base de datos.', 'Revisar DB_HOST (en el servidor debe ser localhost), DB_PASSWORD y que PostgreSQL esté activo.'];
        }
        $this->hayBase = true;
        $ms = (int) round((microtime(true) - $inicio) * 1000);

        return [$ms > 1500 ? 'alerta' : 'ok', "Conectada ({$ms} ms).", null];
    }

    private function esquema(): array
    {
        if (DB::getDriverName() !== 'pgsql') {
            return ['ok', 'No aplica en este tipo de base.'];
        }

        $nombre = $this->nombreEsquema();
        $existe = DB::selectOne('select 1 as existe from information_schema.schemata where schema_name = ?', [$nombre]);

        return $existe
            ? ['ok', "El esquema «{$nombre}» existe."]
            : ['falla', "Falta el esquema «{$nombre}».", 'php artisan nexo:salud --reparar'];
    }

    private function migraciones(): array
    {
        if (! Schema::hasTable('migrations')) {
            return ['falla', 'Falta la tabla de control de migraciones.', 'php artisan nexo:salud --reparar'];
        }

        $migrador = app('migrator');
        $archivos = array_keys($migrador->getMigrationFiles(database_path('migrations')));
        $pendientes = array_diff($archivos, $migrador->getRepository()->getRan());

        return $pendientes === []
            ? ['ok', count($archivos).' migraciones aplicadas.']
            : ['falla', count($pendientes).' migraciones pendientes.', 'php artisan migrate --force'];
    }

    private function tablas(): array
    {
        $faltan = array_values(array_filter(self::TABLAS_CLAVE, fn ($t) => ! Schema::hasTable($t)));

        return $faltan === []
            ? ['ok', 'Están todas las tablas principales.']
            : ['falla', 'Faltan tablas: '.implode(', ', $faltan).'.', 'php artisan nexo:salud --reparar'];
    }

    private function datosIniciales(): array
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('users')) {
            return ['omitido', 'Faltan las tablas; se evaluará cuando existan.'];
        }

        $roles = DB::table('roles')->count();
        $admin = User::where('rol', Rol::Administrador->value)->exists();

        return $roles > 0 && $admin
            ? ['ok', "Hay {$roles} roles y al menos un administrador."]
            : ['falla', 'La base está vacía: faltan roles o el administrador.', 'php artisan nexo:salud --reparar'];
    }

    private function almacenamiento(): array
    {
        $rutas = [
            storage_path('logs'), storage_path('framework/cache'), storage_path('framework/sessions'),
            storage_path('framework/views'), base_path('bootstrap/cache'),
        ];
        $sinPermiso = array_values(array_filter($rutas, fn ($r) => ! is_dir($r) || ! is_writable($r)));

        return $sinPermiso === []
            ? ['ok', 'Todas las carpetas permiten escribir.']
            : ['falla', 'Sin permiso de escritura: '.implode(', ', array_map('basename', $sinPermiso)).'.', 'chmod -R 775 storage bootstrap/cache'];
    }

    private function claveApp(): array
    {
        return config('app.key')
            ? ['ok', 'La clave está definida.']
            : ['falla', 'Falta APP_KEY.', 'php artisan key:generate'];
    }

    private function configuracion(): array
    {
        return config('app.env') === 'production' && config('app.debug')
            ? ['alerta', 'APP_DEBUG está activo en producción.', 'Poner APP_DEBUG=false y ejecutar php artisan config:cache']
            : ['ok', 'Configuración correcta.'];
    }

    private function extensiones(): array
    {
        $faltan = array_values(array_filter(self::EXTENSIONES, fn ($e) => ! extension_loaded($e)));
        // pdo_pgsql solo es obligatoria si la base real es PostgreSQL.
        if (DB::getDriverName() !== 'pgsql') {
            $faltan = array_values(array_diff($faltan, ['pdo_pgsql']));
        }

        return $faltan === []
            ? ['ok', 'Están todas las extensiones.']
            : ['alerta', 'Faltan extensiones: '.implode(', ', $faltan).'.'];
    }

    /** Solo mira la configuración: nunca llama a Stripe, para no depender de un tercero. */
    private function pagos(): array
    {
        if (config('services.payments.driver') !== 'stripe') {
            return ['omitido', 'Pasarela simulada: no se usa Stripe.'];
        }

        $faltan = array_keys(array_filter([
            'STRIPE_KEY' => ! config('services.stripe.key'),
            'STRIPE_SECRET' => ! config('services.stripe.secret'),
            'STRIPE_WEBHOOK_SECRET' => ! config('services.stripe.webhook_secret'),
        ]));

        return $faltan === []
            ? ['ok', 'Stripe tiene sus claves configuradas.']
            : ['falla', 'Faltan claves de Stripe: '.implode(', ', $faltan).'.', 'Agregarlas al .env y ejecutar php artisan config:cache'];
    }

    private function mantenimiento(): array
    {
        return app()->isDownForMaintenance()
            ? ['alerta', 'El sitio está en mantenimiento.', 'php artisan up']
            : ['ok', 'El sitio está activo.'];
    }

    private function nombreEsquema(): string
    {
        $ruta = config('database.connections.'.config('database.default').'.search_path', 'public');

        return trim(explode(',', (string) $ruta)[0]);
    }
}
