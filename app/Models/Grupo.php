<?php

namespace App\Models;

use App\Enums\EstadoPlanificacion;
use App\Enums\Modalidad;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /** Fase según las fechas: por-iniciar, en-curso, finalizado o cancelado. */
    protected function fase(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->estado === EstadoPlanificacion::Cancelado) {
                return 'cancelado';
            }

            return match (true) {
                today()->lt($this->fecha_inicio) => 'por-iniciar',
                today()->gt($this->fecha_fin) => 'finalizado',
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
}
