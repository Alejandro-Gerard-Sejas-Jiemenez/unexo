<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Aula extends Model
{
    use HasUuids;

    protected $table = 'aulas';

    protected $fillable = ['nombre', 'capacidad'];

    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class);
    }
}
