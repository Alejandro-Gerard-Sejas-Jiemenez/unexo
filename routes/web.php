<?php

use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

/*
 * La SPA de React vive directamente en la raíz (/) y resuelve su propio
 * enrutamiento interno. Cualquier ruta heredada bajo /app redirige a la raíz
 * para evitar rutas duplicadas.
 */
Route::get('/app/{cualquiera?}', fn (?string $cualquiera = null) => redirect('/'.ltrim((string) $cualquiera, '/')))
    ->where('cualquiera', '.*');

/*
 * Salud pública para monitores. Sin el grupo web: no abre sesión ni escribe en
 * la base, así responde aunque esa base esté caída o vacía.
 */
Route::get('/health', HealthController::class)->withoutMiddleware('web')->name('health');

Route::view('/{cualquiera?}', 'spa')
    ->where('cualquiera', '^(?!api|up|health|sanctum|storage|images|build|docs).*$')
    ->name('spa');
