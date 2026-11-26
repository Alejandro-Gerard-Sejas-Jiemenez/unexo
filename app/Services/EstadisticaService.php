<?php

namespace App\Services;

use App\Enums\EstadoMatricula;
use App\Models\Bitacora;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\Transaccion;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EstadisticaService
{
    public function __construct(private readonly VisitaService $visitas) {}

    public function resumen(): array
    {
        return Cache::remember('panel.estadisticas.resumen', 60, fn () => [
            'negocio' => $this->negocio(),
            'acceso' => $this->acceso(),
        ]);
    }

    private function negocio(): array
    {
        $porEstado = Matricula::query()
            ->select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $porOrigen = Matricula::query()
            ->select('origen', DB::raw('count(*) as total'))
            ->groupBy('origen')
            ->pluck('total', 'origen');

        $porModalidad = Grupo::query()
            ->select('modalidad', DB::raw('count(*) as total'))
            ->groupBy('modalidad')
            ->pluck('total', 'modalidad');

        $catalogoPorEstado = Programa::query()
            ->select('estado_comercial', DB::raw('count(*) as total'))
            ->groupBy('estado_comercial')
            ->pluck('total', 'estado_comercial');

        $pagos = Transaccion::query()
            ->selectRaw("COALESCE(SUM(CASE WHEN estado = 'aprobada' THEN monto ELSE 0 END), 0) as recaudado")
            ->selectRaw("COALESCE(SUM(CASE WHEN estado = 'pendiente' THEN monto ELSE 0 END), 0) as pendiente")
            ->first();

        return [
            'tarjetas' => [
                ['clave' => 'programas', 'etiqueta' => 'Programas', 'valor' => (int) $catalogoPorEstado->sum()],
                ['clave' => 'modulos', 'etiqueta' => 'Módulos', 'valor' => Modulo::count()],
                ['clave' => 'ediciones', 'etiqueta' => 'Ediciones', 'valor' => Cohorte::count()],
                ['clave' => 'grupos', 'etiqueta' => 'Grupos', 'valor' => (int) $porModalidad->sum()],
                ['clave' => 'matriculas', 'etiqueta' => 'Matrículas', 'valor' => (int) $porEstado->sum()],
                ['clave' => 'estudiantes', 'etiqueta' => 'Estudiantes', 'valor' => User::where('rol', 'estudiante')->count()],
                ['clave' => 'recaudado', 'etiqueta' => 'Recaudado (Bs)', 'valor' => round((float) ($pagos->recaudado ?? 0), 2)],
                ['clave' => 'saldo_pendiente', 'etiqueta' => 'Por cobrar (Bs)', 'valor' => round((float) ($pagos->pendiente ?? 0), 2)],
            ],
            'matriculas_por_estado' => $this->serie($porEstado, [
                'pendiente' => 'Pendiente de pago',
                'activo' => 'Activa',
                'retirado' => 'Retirada',
            ]),
            'matriculas_por_origen' => $this->serie($porOrigen, [
                'manual' => 'Carga manual',
                'crm' => 'Desde el CRM',
            ]),
            'grupos_por_modalidad' => $this->serie($porModalidad, [
                'virtual' => 'Virtual',
                'presencial' => 'Presencial',
                'mixta' => 'Mixta',
            ]),
            'catalogo_por_estado' => $this->serie($catalogoPorEstado, [
                'borrador' => 'Borrador',
                'abierto' => 'Abierto',
                'cerrado' => 'Cerrado',
            ]),
            'ocupacion_grupos' => $this->ocupacion(),
        ];
    }

    private function acceso(): array
    {
        $desde = now()->subDays(29)->startOfDay();
        $exprDia = DB::connection()->getDriverName() === 'pgsql'
            ? 'CAST(created_at AS DATE)'
            : 'date(created_at)';

        $porDia = Bitacora::query()
            ->where('evento', 'login_exitoso')
            ->where('created_at', '>=', $desde)
            ->selectRaw("{$exprDia} as dia, count(*) as total")
            ->groupBy('dia')
            ->orderBy('dia')
            ->get()
            ->map(fn ($f) => ['etiqueta' => (string) $f->dia, 'valor' => (int) $f->total])
            ->all();

        $recursos = Bitacora::query()
            ->where('evento', 'acceso_recurso')
            ->whereNotNull('recurso')
            ->select('recurso', DB::raw('count(*) as total'))
            ->groupBy('recurso')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($f) => ['etiqueta' => $f->recurso, 'valor' => (int) $f->total])
            ->all();

        $resumenBitacora = Bitacora::query()
            ->selectRaw("SUM(CASE WHEN evento = 'login_exitoso' THEN 1 ELSE 0 END) as logins")
            ->selectRaw("SUM(CASE WHEN evento = 'login_fallido' THEN 1 ELSE 0 END) as fallidos")
            ->selectRaw('COUNT(DISTINCT ip) as ips')
            ->first();

        return [
            'tarjetas' => [
                [
                    'clave' => 'visitas',
                    'etiqueta' => 'Visitas totales',
                    'valor' => $this->visitas->totalDelSitio(),
                ],
                [
                    'clave' => 'logins',
                    'etiqueta' => 'Inicios de sesión',
                    'valor' => (int) ($resumenBitacora->logins ?? 0),
                ],
                [
                    'clave' => 'fallidos',
                    'etiqueta' => 'Intentos fallidos',
                    'valor' => (int) ($resumenBitacora->fallidos ?? 0),
                ],
                [
                    'clave' => 'ips',
                    'etiqueta' => 'Direcciones IP distintas',
                    'valor' => (int) ($resumenBitacora->ips ?? 0),
                ],
            ],
            'logins_por_dia' => $porDia,
            'recursos_mas_accedidos' => $recursos,
            'paginas_mas_visitadas' => array_map(
                fn (array $v) => ['etiqueta' => $v['ruta'], 'valor' => $v['contador']],
                $this->visitas->masVisitadas(10),
            ),
        ];
    }

    private function ocupacion(): array
    {
        $estados = array_map(fn (EstadoMatricula $e) => $e->value, EstadoMatricula::queCuentanComoInscritos());

        return Grupo::query()
            ->with(['modulo:id,nombre', 'cohorte:id,nombre'])
            ->withCount(['inscripciones as inscritos_count' => fn ($q) => $q->whereHas(
                'matricula',
                fn ($m) => $m->whereIn('estado', $estados),
            )])
            ->whereNotNull('cupo_minimo')
            ->orderByDesc('inscritos_count')
            ->limit(10)
            ->get()
            ->map(function (Grupo $g): array {
                $contexto = $g->modulo?->nombre ?? 'Módulo';
                if ($g->cohorte) {
                    $contexto .= ' · '.$g->cohorte->nombre;
                }

                return [
                    'id' => $g->id,
                    'etiqueta' => "{$contexto} ({$g->nombre})",
                    'valor' => (int) $g->inscritos_count,
                    'meta' => (int) $g->cupo_minimo,
                ];
            })
            ->all();
    }

    private function serie(mixed $conteo, array $etiquetas): array
    {
        $salida = [];

        foreach ($etiquetas as $clave => $etiqueta) {
            $salida[] = [
                'clave' => $clave,
                'etiqueta' => $etiqueta,
                'valor' => (int) ($conteo[$clave] ?? 0),
            ];
        }

        return $salida;
    }
}
