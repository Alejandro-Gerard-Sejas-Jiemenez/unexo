<?php

use Illuminate\Support\Facades\Route;

/*
 * La SPA de React vive directamente en la raíz (/) y resuelve su propio
 * enrutamiento interno. Cualquier ruta heredada bajo /app redirige a la raíz
 * para evitar rutas duplicadas.
 */
Route::get('/app/{cualquiera?}', fn (?string $cualquiera = null) => redirect('/'.ltrim((string) $cualquiera, '/')))
    ->where('cualquiera', '.*');

Route::view('/{cualquiera?}', 'spa')
    ->where('cualquiera', '^(?!api|up|sanctum|storage|images|build|docs).*$')
    ->name('spa');
