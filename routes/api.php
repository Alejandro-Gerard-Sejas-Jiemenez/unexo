<?php

use App\Http\Controllers\Api\CrmCatalogoApiController;
use App\Http\Controllers\Api\EstudianteConvertidoController;
use App\Http\Controllers\Api\MatriculaApiController;
use App\Http\Controllers\Api\Panel\AuditoriaApiController;
use App\Http\Controllers\Api\Panel\AuthController;
use App\Http\Controllers\Api\Panel\BuscadorApiController;
use App\Http\Controllers\Api\Panel\CatalogoApiController;
use App\Http\Controllers\Api\Panel\MenuController;
use App\Http\Controllers\Api\Panel\PagoApiController;
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

        // Visibles para cualquier rol: el contador va en el pie de todas las vistas
        // y el buscador en el encabezado, ya filtrado por permisos en el servidor.
        Route::get('visitas', [AuditoriaApiController::class, 'visitas']);
        Route::get('buscar', BuscadorApiController::class);

        // Pagos: cada usuario administra sus propios métodos.
        Route::get('metodos-pago', [PagoApiController::class, 'metodos']);
        Route::post('metodos-pago', [PagoApiController::class, 'guardarMetodo']);
        Route::delete('metodos-pago/{metodo}', [PagoApiController::class, 'eliminarMetodo'])->whereUuid('metodo');
        Route::get('matriculas/{matricula}/transacciones', [PagoApiController::class, 'transacciones'])->whereUuid('matricula');

        // Catálogo: lo consultan todos los roles; solo el administrador lo edita.
        Route::get('catalogo/opciones', [CatalogoApiController::class, 'opciones']);
        Route::get('catalogo/programas', [CatalogoApiController::class, 'programas']);
        Route::get('catalogo/programas/{programa}', [CatalogoApiController::class, 'programa'])->whereUuid('programa');
        Route::get('catalogo/modulos', [CatalogoApiController::class, 'modulos']);
        Route::get('catalogo/modulos/{modulo}', [CatalogoApiController::class, 'modulo'])->whereUuid('modulo');

        Route::middleware('rol:administrador')->group(function () {
            Route::post('catalogo/programas', [CatalogoApiController::class, 'guardarPrograma']);
            Route::put('catalogo/programas/{programa}', [CatalogoApiController::class, 'actualizarPrograma'])->whereUuid('programa');
            Route::post('catalogo/programas/{programa}/modulos', [CatalogoApiController::class, 'agregarModulo'])->whereUuid('programa');
            Route::delete('catalogo/programas/{programa}/modulos/{modulo}', [CatalogoApiController::class, 'quitarModulo'])->whereUuid('programa')->whereUuid('modulo');
            Route::patch('catalogo/programas/{programa}/modulos/{modulo}/mover', [CatalogoApiController::class, 'moverModulo'])->whereUuid('programa')->whereUuid('modulo');
            Route::post('catalogo/modulos', [CatalogoApiController::class, 'guardarModulo']);
            Route::put('catalogo/modulos/{modulo}', [CatalogoApiController::class, 'actualizarModulo'])->whereUuid('modulo');

            Route::get('bitacora', [AuditoriaApiController::class, 'bitacora']);
            Route::get('estadisticas', [AuditoriaApiController::class, 'estadisticas']);

            Route::post('matriculas/{matricula}/cobrar', [PagoApiController::class, 'cobrar'])->whereUuid('matricula');
            Route::patch('transacciones/{transaccion}/aprobar', [PagoApiController::class, 'aprobar'])->whereUuid('transaccion');
            Route::patch('transacciones/{transaccion}/rechazar', [PagoApiController::class, 'rechazar'])->whereUuid('transaccion');

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
