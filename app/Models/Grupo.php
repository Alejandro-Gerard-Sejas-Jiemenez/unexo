<?php

namespace App\Models;

use App\Enums\EstadoPlanificacion;
use App\Enums\Modalidad;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $modulo_id
 * @property string|null $cohorte_id
 * @property string $docente_id
 * @property string|null $aula_id
 * @property string $nombre
 * @property int|null $numero
 * @property Carbon|null $fecha_inicio Solo grupos sueltos; los de edición usan la de la cohorte.
 * @property Carbon|null $fecha_fin Solo grupos sueltos; los de edición usan la de la cohorte.
 * @property-read Carbon|null $periodo_inicio
 * @property-read Carbon|null $periodo_fin
 * @property-read string $periodo_texto
 * @property Modalidad $modalidad
 * @property string|null $enlace_videoconferencia
 * @property int|null $cupo_minimo
 * @property EstadoPlanificacion $estado
 * @property-read Modulo $modulo
 * @property-read Cohorte|null $cohorte
 * @property-read User $docente
 * @property-read Aula|null $aula
 * @property-read Collection<int, HorarioGrupo> $horarios
 */
class Grupo extends Model
{
    use HasUuids;

    protected $table = 'grupos';

    protected $fillable = [
        'modulo_id',
        'cohorte_id',
        'docente_id',
        'aula_id',
        'nombre',
        'numero',
        'fecha_inicio',
        'fecha_fin',
        'modalidad',
        'enlace_videoconferencia',
        'cupo_minimo',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'modalidad' => Modalidad::class,
            'estado' => EstadoPlanificacion::class,
        ];
    }

    /** Inicio efectivo: el de la edición si el grupo es de edición; si no, el propio. */
    protected function periodoInicio(): Attribute
    {
        return Attribute::get(fn (): ?Carbon => $this->cohorte_id ? $this->cohorte?->fecha_inicio : $this->fecha_inicio);
    }

    /** Fin efectivo: el de la edición si el grupo es de edición; si no, el propio. */
    protected function periodoFin(): Attribute
    {
        return Attribute::get(fn (): ?Carbon => $this->cohorte_id ? $this->cohorte?->fecha_fin : $this->fecha_fin);
    }

    /** Período efectivo legible, p. ej. "01/03/2026 – 30/03/2026". */
    protected function periodoTexto(): Attribute
    {
        return Attribute::get(function (): string {
            if (! $this->periodo_inicio || ! $this->periodo_fin) {
                return 'Sin período definido';
            }

            return $this->periodo_inicio->format('d/m/Y').' – '.$this->periodo_fin->format('d/m/Y');
        });
    }

    /** Fase según el período efectivo: por-iniciar, en-curso, finalizado o cancelado. */
    protected function fase(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->estado === EstadoPlanificacion::Cancelado) {
                return 'cancelado';
            }

            $inicio = $this->periodo_inicio;
            $fin = $this->periodo_fin;

            return match (true) {
                $inicio === null || $fin === null => 'por-iniciar',
                today()->lt($inicio) => 'por-iniciar',
                today()->gt($fin) => 'finalizado',
                default => 'en-curso',
            };
        });
    }

    protected function faseEtiqueta(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->fase) {
            'por-iniciar' => 'Por iniciar',
            'en-curso' => 'En curso',
            'finalizado' => 'Finalizado',
            default => 'Cancelado',
        });
    }

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class);
    }

    public function cohorte(): BelongsTo
    {
        return $this->belongsTo(Cohorte::class);
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'docente_id');
    }

    public function aula(): BelongsTo
    {
        return $this->belongsTo(Aula::class);
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(InscripcionGrupo::class);
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioGrupo::class)->orderBy('dia_semana')->orderBy('hora_inicio');
    }

    /** Estado de ocupación o quórum opcional respecto al cupo mínimo. */
    protected function quorum(): Attribute
    {
        return Attribute::get(function (): array {
            $inscritos = $this->inscripciones_count ?? $this->inscripciones()->count();
            $minimo = (int) ($this->cupo_minimo ?? 0);
            $tieneLimite = $minimo > 0;
            $alcanzado = $tieneLimite ? ($inscritos >= $minimo) : true;
            $porcentaje = $tieneLimite ? min(100.0, round(($inscritos / $minimo) * 100, 1)) : 100.0;
            $restante = $tieneLimite ? max(0, $minimo - $inscritos) : 0;

            return compact('inscritos', 'minimo', 'alcanzado', 'porcentaje', 'restante') + ['tiene_limite' => $tieneLimite];
        });
    }

    public function scopeSueltos($query)
    {
        return $query->whereNull('cohorte_id');
    }

    public function scopeDeCohorte($query, ?string $cohorteId = null)
    {
        if ($cohorteId) {
            return $query->where('cohorte_id', $cohorteId);
        }

        return $query->whereNotNull('cohorte_id');
    }

    /** Subconsulta del período efectivo: la edición manda; el grupo suelto usa su propia columna. */
    private static function expresionPeriodo(string $columna): string
    {
        return "date(COALESCE((SELECT c.$columna FROM cohortes c WHERE c.id = grupos.cohorte_id), grupos.$columna))";
    }

    /** Orden por inicio del período efectivo (009 D4); sin JOIN para no alterar las columnas seleccionadas. */
    public function scopeOrdenadoPorPeriodo($query, string $direccion = 'asc')
    {
        $direccion = strtolower($direccion) === 'desc' ? 'desc' : 'asc';

        return $query->orderByRaw(self::expresionPeriodo('fecha_inicio').' '.$direccion);
    }

    /** Grupos cuyo período efectivo toca el rango [$desde, $hasta], extremos incluidos. */
    public function scopeQueSolapaCon($query, ?string $desde, ?string $hasta)
    {
        if ($hasta !== null) {
            $query->whereRaw(self::expresionPeriodo('fecha_inicio').' <= date(?)', [$hasta]);
        }

        if ($desde !== null) {
            $query->whereRaw(self::expresionPeriodo('fecha_fin').' >= date(?)', [$desde]);
        }

        return $query;
    }

    /** Grupos que el CRM puede ver según su estado. */
    public function scopeVisibleParaCrm($query)
    {
        return $query->whereIn('estado', array_map(
            fn (EstadoPlanificacion $estado) => $estado->value,
            EstadoPlanificacion::visiblesParaCrm(),
        ));
    }
}
