<?php

use App\Http\Controllers\Api\CrmCatalogoApiController;
use App\Http\Controllers\Api\EstudianteConvertidoController;
use App\Http\Controllers\Api\MatriculaApiController;
use App\Http\Controllers\Api\Panel\AuditoriaApiController;
use App\Http\Controllers\Api\Panel\AuthController;
use App\Http\Controllers\Api\Panel\BuscadorApiController;
use App\Http\Controllers\Api\Panel\CatalogoApiController;
use App\Http\Controllers\Api\Panel\ComprobanteApiController;
use App\Http\Controllers\Api\Panel\ConfiguracionApiController;
use App\Http\Controllers\Api\Panel\InicioApiController;
use App\Http\Controllers\Api\Panel\MatriculaPanelApiController;
use App\Http\Controllers\Api\Panel\MenuController;
use App\Http\Controllers\Api\Panel\MisClasesApiController;
use App\Http\Controllers\Api\Panel\PagoApiController;
use App\Http\Controllers\Api\Panel\PlanificacionApiController;
use App\Http\Controllers\Api\Panel\ReporteApiController;
use App\Http\Controllers\Api\Panel\UsuarioApiController;
use App\Http\Controllers\Api\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('panel')->middleware('web')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::get('visitas', [AuditoriaApiController::class, 'visitas']);
    Route::post('visitas', [AuditoriaApiController::class, 'registrarVisita']);

    Route::middleware(['auth:sanctum', 'usuario.panel'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::get('menu', MenuController::class);
        Route::get('buscar', BuscadorApiController::class);

        Route::get('perfil', [UsuarioApiController::class, 'perfil']);
        Route::put('perfil', [UsuarioApiController::class, 'actualizarPerfil']);

        Route::get('metodos-pago', [PagoApiController::class, 'metodos']);
        Route::post('metodos-pago', [PagoApiController::class, 'guardarMetodo']);
        Route::delete('metodos-pago/{metodo}', [PagoApiController::class, 'eliminarMetodo'])->whereUuid('metodo');
        Route::get('matriculas/{matricula}/transacciones', [PagoApiController::class, 'transacciones'])->whereUuid('matricula');
        Route::post('matriculas/{matricula}/pagar', [PagoApiController::class, 'pagarMatricula'])->whereUuid('matricula');
        Route::post('transacciones/{transaccion}/pagar', [PagoApiController::class, 'pagarTransaccion'])->whereUuid('transaccion');

        Route::get('comprobantes/{comprobante}', [ComprobanteApiController::class, 'ver'])
            ->whereUuid('comprobante')
            ->middleware('rol:administrador,estudiante');

        Route::get('inicio', [InicioApiController::class, 'inicio']);
        Route::get('catalogo-publico', [InicioApiController::class, 'catalogo']);

        Route::get('calendario', [MisClasesApiController::class, 'calendario'])->middleware('rol:docente,estudiante');
        Route::middleware('rol:docente')->group(function () {
            Route::get('mis-grupos', [MisClasesApiController::class, 'misGrupos']);
            Route::get('mis-grupos/{grupo}', [MisClasesApiController::class, 'miGrupo'])->whereUuid('grupo');
        });
        Route::middleware('rol:estudiante')->group(function () {
            Route::get('mis-cursos', [MisClasesApiController::class, 'misCursos']);
            Route::get('mis-cursos/{grupo}', [MisClasesApiController::class, 'miCurso'])->whereUuid('grupo');
            Route::get('mis-matriculas', [PagoApiController::class, 'misMatriculas']);
            Route::post('matriculas/{matricula}/comprobantes', [ComprobanteApiController::class, 'store'])->whereUuid('matricula');
        });

        Route::get('catalogo/programas', [CatalogoApiController::class, 'programas']);
        Route::get('catalogo/programas/{programa}', [CatalogoApiController::class, 'programa'])->whereUuid('programa');
        Route::get('catalogo/modulos', [CatalogoApiController::class, 'modulos']);
        Route::get('catalogo/modulos/{modulo}', [CatalogoApiController::class, 'modulo'])->whereUuid('modulo');

        Route::middleware('rol:administrador')->group(function () {
            Route::get('catalogo/opciones', [CatalogoApiController::class, 'opciones']);
            Route::post('catalogo/programas', [CatalogoApiController::class, 'guardarPrograma']);
            Route::put('catalogo/programas/{programa}', [CatalogoApiController::class, 'actualizarPrograma'])->whereUuid('programa');
            Route::post('catalogo/programas/{programa}/modulos', [CatalogoApiController::class, 'agregarModulo'])->whereUuid('programa');
            Route::delete('catalogo/programas/{programa}/modulos/{modulo}', [CatalogoApiController::class, 'quitarModulo'])->whereUuid('programa')->whereUuid('modulo');
            Route::patch('catalogo/programas/{programa}/modulos/{modulo}/mover', [CatalogoApiController::class, 'moverModulo'])->whereUuid('programa')->whereUuid('modulo');
            Route::post('catalogo/modulos', [CatalogoApiController::class, 'guardarModulo']);
            Route::put('catalogo/modulos/{modulo}', [CatalogoApiController::class, 'actualizarModulo'])->whereUuid('modulo');

            Route::get('planificacion/opciones', [PlanificacionApiController::class, 'opciones']);
            Route::get('cohortes', [PlanificacionApiController::class, 'cohortes']);
            Route::post('cohortes', [PlanificacionApiController::class, 'guardarCohorte']);
            Route::get('cohortes/{cohorte}', [PlanificacionApiController::class, 'cohorte'])->whereUuid('cohorte');
            Route::put('cohortes/{cohorte}', [PlanificacionApiController::class, 'actualizarCohorte'])->whereUuid('cohorte');
            Route::post('cohortes/{cohorte}/aperturar-grupos', [PlanificacionApiController::class, 'aperturarGrupos'])->whereUuid('cohorte');
            Route::patch('cohortes/{cohorte}/estado', [PlanificacionApiController::class, 'cambiarEstadoCohorte'])->whereUuid('cohorte');

            Route::get('matriculas/opciones', [MatriculaPanelApiController::class, 'opciones']);
            Route::get('matriculas', [MatriculaPanelApiController::class, 'index']);
            Route::post('matriculas', [MatriculaPanelApiController::class, 'store']);
            Route::get('matriculas/{matricula}', [MatriculaPanelApiController::class, 'show'])->whereUuid('matricula');
            Route::patch('matriculas/{matricula}/estado', [MatriculaPanelApiController::class, 'cambiarEstado'])->whereUuid('matricula');

            Route::patch('comprobantes/{comprobante}/aprobar', [ComprobanteApiController::class, 'aprobar'])->whereUuid('comprobante');
            Route::patch('comprobantes/{comprobante}/rechazar', [ComprobanteApiController::class, 'rechazar'])->whereUuid('comprobante');

            Route::get('grupos', [PlanificacionApiController::class, 'gruposIndex']);
            Route::post('grupos', [PlanificacionApiController::class, 'guardarGrupo']);
            Route::get('grupos/{grupo}', [PlanificacionApiController::class, 'grupo'])->whereUuid('grupo');
            Route::put('grupos/{grupo}', [PlanificacionApiController::class, 'actualizarGrupo'])->whereUuid('grupo');
            Route::patch('grupos/{grupo}/docente', [PlanificacionApiController::class, 'cambiarDocenteGrupo'])->whereUuid('grupo');

            Route::get('reportes/matriculas', [ReporteApiController::class, 'matriculas']);
            Route::get('reportes/matriculas/pdf', [ReporteApiController::class, 'matriculasPdf']);
            Route::get('reportes/matriculas/excel', [ReporteApiController::class, 'matriculasExcel']);
            Route::get('reportes/ocupacion', [ReporteApiController::class, 'ocupacion']);
            Route::get('reportes/ocupacion/pdf', [ReporteApiController::class, 'ocupacionPdf']);
            Route::get('reportes/ocupacion/excel', [ReporteApiController::class, 'ocupacionExcel']);
            Route::get('reportes/catalogo', [ReporteApiController::class, 'catalogo']);
            Route::get('reportes/catalogo/pdf', [ReporteApiController::class, 'catalogoPdf']);
            Route::get('reportes/catalogo/excel', [ReporteApiController::class, 'catalogoExcel']);

            Route::get('aulas', [ConfiguracionApiController::class, 'aulas']);
            Route::post('aulas', [ConfiguracionApiController::class, 'guardarAula']);
            Route::put('aulas/{aula}', [ConfiguracionApiController::class, 'actualizarAula'])->whereUuid('aula');
            Route::get('clientes-api', [ConfiguracionApiController::class, 'clientesApi']);
            Route::post('clientes-api', [ConfiguracionApiController::class, 'crearClienteApi']);
            Route::delete('clientes-api/{cliente}', [ConfiguracionApiController::class, 'revocarClienteApi'])->whereUuid('cliente');

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

Route::post('webhooks/stripe', StripeWebhookController::class)->middleware('throttle:60,1');

Route::prefix('v1')->middleware(['auth:sanctum', 'cliente.api'])->group(function () {
    Route::get('crm/catalogo', [CrmCatalogoApiController::class, 'index']);
    Route::get('crm/programas', [CrmCatalogoApiController::class, 'programas']);
    Route::get('crm/programas/{programa}', [CrmCatalogoApiController::class, 'programa'])->whereUuid('programa');
    Route::get('crm/ediciones', [CrmCatalogoApiController::class, 'ediciones']);
    Route::get('crm/ediciones/{cohorte}', [CrmCatalogoApiController::class, 'edicion'])->whereUuid('cohorte');
    Route::get('crm/modulos', [CrmCatalogoApiController::class, 'modulos']);
    Route::get('crm/modulos/{modulo}', [CrmCatalogoApiController::class, 'modulo'])->whereUuid('modulo');

    Route::post('estudiantes-convertidos', [EstudianteConvertidoController::class, 'store']);
    Route::get('matriculas', [MatriculaApiController::class, 'index']);
    Route::get('matriculas/{matricula}', [MatriculaApiController::class, 'show'])->whereUuid('matricula');
});
