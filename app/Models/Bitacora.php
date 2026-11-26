<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bitacora extends Model
{
    use HasUuids;

    protected $table = 'bitacora';

    /** Solo se escribe una vez: no tiene sentido un updated_at. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'evento',
        'identificador',
        'recurso',
        'metodo_http',
        'codigo_respuesta',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
