<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class VisitaPagina extends Model
{
    use HasUuids;

    protected $table = 'visitas_pagina';

    protected $fillable = ['ruta', 'contador', 'ultima_visita'];

    protected function casts(): array
    {
        return [
            'contador' => 'integer',
            'ultima_visita' => 'datetime',
        ];
    }
}
