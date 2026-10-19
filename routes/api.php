<?php

use App\Http\Controllers\Api\CrmCatalogoApiController;
use App\Http\Controllers\Api\EstudianteConvertidoController;
use App\Http\Controllers\Api\MatriculaApiController;
use Illuminate\Support\Facades\Route;

// Solo clientes de la API (el CRM) con token Bearer de Sanctum; un usuario del panel no entra por aquí.
Route::prefix('v1')->middleware(['auth:sanctum', 'cliente.api'])->group(function () {
    // Oferta comercial y catálogo académico para el CRM
    Route::get('crm/catalogo', [CrmCatalogoApiController::class, 'index']);
    Route::get('crm/programas', [CrmCatalogoApiController::class, 'programas']);
    Route::get('crm/programas/{programa}', [CrmCatalogoApiController::class, 'programa'])->whereUuid('programa');
    Route::get('crm/ediciones', [CrmCatalogoApiController::class, 'ediciones']);
    Route::get('crm/ediciones/{cohorte}', [CrmCatalogoApiController::class, 'edicion'])->whereUuid('cohorte');
    Route::get('crm/modulos', [CrmCatalogoApiController::class, 'modulos']);
    Route::get('crm/modulos/{modulo}', [CrmCatalogoApiController::class, 'modulo'])->whereUuid('modulo');

    // Conversión de prospectos e información de matrículas
    Route::post('estudiantes-convertidos', [EstudianteConvertidoController::class, 'store']);
    Route::get('matriculas', [MatriculaApiController::class, 'index']);
    Route::get('matriculas/{matricula}', [MatriculaApiController::class, 'show']);
});
