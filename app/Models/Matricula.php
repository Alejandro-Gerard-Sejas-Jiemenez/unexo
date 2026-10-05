<?php

namespace App\Models;

use App\Enums\EstadoMatricula;
use App\Enums\OrigenMatricula;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Matricula extends Model
{
    use HasUuids;

    protected $table = 'matriculas';

    protected $fillable = [
        'estudiante_id',
        'cohorte_id',
        'estado',
        'origen',
        'id_externo_crm',
        'fecha_matricula',
        'fecha_retiro',
    ];

    protected function casts(): array
    {
        return [
            'fecha_matricula' => 'date',
            'fecha_retiro' => 'date',
            'estado' => EstadoMatricula::class,
            'origen' => OrigenMatricula::class,
        ];
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }

    public function cohorte(): BelongsTo
    {
        return $this->belongsTo(Cohorte::class);
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(InscripcionGrupo::class);
    }
}
