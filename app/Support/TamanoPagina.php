<?php

namespace App\Support;

/** Filas por página que pidió el usuario en las listas del panel. */
class TamanoPagina
{
    /** Solo valen los tamaños permitidos: así nadie puede pedir toda la tabla de una vez. */
    public static function elegido(): int
    {
        $pedido = (string) request()->query('per_page', '');

        return ctype_digit($pedido) && in_array((int) $pedido, config('limites.tamanos_pagina'), true)
            ? (int) $pedido
            : (int) config('limites.por_pagina');
    }
}
