<?php

namespace App\Services;

use App\Models\Bitacora;
use App\Models\Cohorte;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Modulo;
use App\Models\Programa;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Métricas del panel analítico: las del negocio y las de acceso al sitio.
 * Todas son consultas agregadas; ninguna trae filas al PHP para contarlas.
 */
class EstadisticaService
{
    public function __construct(private readonly VisitaService $visitas) {}

    public function resumen(): array
    {
        return [
            'negocio' => $this->negocio(),
            'acceso' => $this->acceso(),
        ];
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

        return [
            'tarjetas' => [
                ['clave' => 'programas', 'etiqueta' => 'Programas', 'valor' => Programa::count()],
                ['clave' => 'modulos', 'etiqueta' => 'Módulos', 'valor' => Modulo::count()],
                ['clave' => 'ediciones', 'etiqueta' => 'Ediciones', 'valor' => Cohorte::count()],
                ['clave' => 'grupos', 'etiqueta' => 'Grupos', 'valor' => Grupo::count()],
                ['clave' => 'matriculas', 'etiqueta' => 'Matrículas', 'valor' => Matricula::count()],
                ['clave' => 'estudiantes', 'etiqueta' => 'Estudiantes', 'valor' => User::where('rol', 'estudiante')->count()],
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

        $porDia = Bitacora::query()
            ->where('evento', 'login_exitoso')
            ->where('created_at', '>=', $desde)
            ->select(DB::raw('date(created_at) as dia'), DB::raw('count(*) as total'))
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
                    'valor' => Bitacora::where('evento', 'login_exitoso')->count(),
                ],
                [
                    'clave' => 'fallidos',
                    'etiqueta' => 'Intentos fallidos',
                    'valor' => Bitacora::where('evento', 'login_fallido')->count(),
                ],
                [
                    'clave' => 'ips',
                    'etiqueta' => 'Direcciones IP distintas',
                    'valor' => Bitacora::distinct()->count('ip'),
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

    /** Inscritos frente al cupo mínimo, por grupo. */
    private function ocupacion(): array
    {
        return Grupo::query()
            ->withCount('inscripciones')
            ->whereNotNull('cupo_minimo')
            ->orderByDesc('inscripciones_count')
            ->limit(10)
            ->get()
            ->map(fn (Grupo $g) => [
                'etiqueta' => $g->nombre,
                'valor' => $g->inscripciones_count,
                'meta' => $g->cupo_minimo,
            ])
            ->all();
    }

    /**
     * Normaliza un conteo por clave a una serie con etiquetas legibles,
     * incluyendo en cero las categorías que todavía no tienen filas.
     */
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
