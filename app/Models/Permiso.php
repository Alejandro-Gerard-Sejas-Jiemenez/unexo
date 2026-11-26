<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permiso extends Model
{
    use HasUuids;

    protected $table = 'permisos';

    protected $fillable = [
        'clave',
        'nombre',
        'modulo',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(RolModelo::class, 'rol_permiso', 'permiso_id', 'rol_id');
    }
}
