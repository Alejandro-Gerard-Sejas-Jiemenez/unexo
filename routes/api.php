<?php

use App\Http\Controllers\Api\CrmCatalogoApiController;
use App\Http\Controllers\Api\EstudianteConvertidoController;
use App\Http\Controllers\Api\MatriculaApiController;
use App\Http\Controllers\Api\Panel\AuthController;
use App\Http\Controllers\Api\Panel\MenuController;
use App\Http\Controllers\Api\Panel\UsuarioApiController;
use Illuminate\Support\Facades\Route;

/*
 * API del panel: la consume la SPA de React con la cookie de sesión.
 * Lleva el grupo 'web' explícito (sesión y CSRF) en vez de depender de que Sanctum
 * reconozca el origen de la petición, que falla cuando no llega Origin ni Referer.
 * Guard distinto al de /v1, que es solo para el CRM con token Bearer.
 */
Route::prefix('panel')->middleware('web')->group(function () {
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::get('menu', MenuController::class);

        Route::middleware('rol:administrador')->group(function () {
            Route::get('usuarios/opciones', [UsuarioApiController::class, 'opciones']);
            Route::get('usuarios', [UsuarioApiController::class, 'index']);
            Route::post('usuarios', [UsuarioApiController::class, 'store']);
            Route::get('usuarios/{usuario}', [UsuarioApiController::class, 'show'])->whereUuid('usuario');
            Route::put('usuarios/{usuario}', [UsuarioApiController::class, 'update'])->whereUuid('usuario');
            Route::patch('usuarios/{usuario}/estado', [UsuarioApiController::class, 'cambiarEstado'])->whereUuid('usuario');
        });
    });
});

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
