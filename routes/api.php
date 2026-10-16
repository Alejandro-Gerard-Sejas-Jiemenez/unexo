<?php

use App\Http\Controllers\Api\EstudianteConvertidoController;
use App\Http\Controllers\Api\MatriculaApiController;
use Illuminate\Support\Facades\Route;

// Solo clientes de la API (el CRM) con token Bearer de Sanctum; un usuario del panel no entra por aquí.
Route::prefix('v1')->middleware(['auth:sanctum', 'cliente.api'])->group(function () {
    Route::post('estudiantes-convertidos', [EstudianteConvertidoController::class, 'store']);
    Route::get('matriculas', [MatriculaApiController::class, 'index']);
    Route::get('matriculas/{matricula}', [MatriculaApiController::class, 'show']);
});
