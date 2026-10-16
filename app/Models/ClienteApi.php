<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

/** Sistema externo (p. ej. el CRM) que consume la API con un token Bearer de Sanctum. */
class ClienteApi extends Model
{
    use HasApiTokens, HasUuids;

    protected $table = 'api_clientes';

    protected $fillable = ['nombre'];
}