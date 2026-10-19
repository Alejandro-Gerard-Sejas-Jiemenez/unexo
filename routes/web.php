<?php

use App\Http\Controllers\AulaController;
use App\Http\Controllers\CalendarioController;
use App\Http\Controllers\ClienteApiController;
use App\Http\Controllers\CohorteController;
use App\Http\Controllers\ComprobanteController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MatriculaController;
use App\Http\Controllers\MisCursosController;
use App\Http\Controllers\MisGruposController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\ProgramaController;
use App\Http\Controllers\ReporteCatalogoController;
use App\Http\Controllers\ReporteMatriculasController;
use App\Http\Controllers\ReporteOcupacionController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/panel', [PanelController::class, 'index'])->name('panel');
    Route::get('/catalogo', [PanelController::class, 'catalogo'])->name('catalogo');

    Route::get('calendario', [CalendarioController::class, 'index'])->middleware('rol:docente,estudiante')->name('calendario');

    Route::get('mis-cursos', [MisCursosController::class, 'index'])->middleware('rol:estudiante')->name('mis-cursos');
    Route::get('mis-cursos/{grupo}', [MisCursosController::class, 'show'])->whereUuid('grupo')->middleware('rol:estudiante')->name('mis-cursos.show');
    Route::post('matriculas/{matricula}/comprobantes', [ComprobanteController::class, 'store'])->middleware('rol:estudiante')->name('comprobantes.store');
    Route::get('comprobantes/{comprobante}', [ComprobanteController::class, 'ver'])->middleware('rol:administrador,estudiante')->name('comprobantes.ver');

    // Reportes: la autorización por rol (solo Administrador) vive en los Form Requests para devolver el texto exacto de RF-02.
    Route::prefix('reportes')->name('reportes.')->group(function () {
        Route::get('matriculas', [ReporteMatriculasController::class, 'index'])->name('matriculas');
        Route::get('matriculas/pdf', [ReporteMatriculasController::class, 'pdf'])->name('matriculas.pdf');
        Route::get('matriculas/excel', [ReporteMatriculasController::class, 'excel'])->name('matriculas.excel');

        Route::get('ocupacion-grupos', [ReporteOcupacionController::class, 'index'])->name('ocupacion');
        Route::get('ocupacion-grupos/pdf', [ReporteOcupacionController::class, 'pdf'])->name('ocupacion.pdf');
        Route::get('ocupacion-grupos/excel', [ReporteOcupacionController::class, 'excel'])->name('ocupacion.excel');

        Route::get('catalogo', [ReporteCatalogoController::class, 'index'])->name('catalogo');
        Route::get('catalogo/pdf', [ReporteCatalogoController::class, 'pdf'])->name('catalogo.pdf');
        Route::get('catalogo/excel', [ReporteCatalogoController::class, 'excel'])->name('catalogo.excel');
    });

    Route::middleware('rol:docente')->group(function () {
        Route::get('mis-grupos', [MisGruposController::class, 'index'])->name('mis-grupos.index');
        Route::get('mis-grupos/{grupo}', [MisGruposController::class, 'show'])->name('mis-grupos.show');
    });

    Route::middleware('rol:administrador')->group(function () {
        Route::resource('usuarios', UsuarioController::class)
            ->parameters(['usuarios' => 'usuario'])
            ->except(['show', 'destroy']);
        Route::patch('usuarios/{usuario}/estado', [UsuarioController::class, 'cambiarEstado'])->name('usuarios.estado');

        Route::resource('programas', ProgramaController::class)
            ->parameters(['programas' => 'programa'])
            ->except(['destroy']);
        Route::post('programas/{programa}/modulos', [ProgramaController::class, 'agregarModulo'])->name('programas.modulos.agregar');
        Route::delete('programas/{programa}/modulos/{modulo}', [ProgramaController::class, 'quitarModulo'])->name('programas.modulos.quitar');
        Route::patch('programas/{programa}/modulos/{modulo}/mover', [ProgramaController::class, 'moverModulo'])->name('programas.modulos.mover');

        Route::resource('modulos', ModuloController::class)
            ->parameters(['modulos' => 'modulo'])
            ->except(['show', 'destroy']);

        Route::resource('cohortes', CohorteController::class)
            ->parameters(['cohortes' => 'cohorte'])
            ->except(['destroy']);
        Route::post('cohortes/{cohorte}/aperturar-grupos', [CohorteController::class, 'aperturarGrupos'])->name('cohortes.aperturar-grupos');
        Route::patch('cohortes/{cohorte}/estado', [CohorteController::class, 'cambiarEstado'])->name('cohortes.estado');

        Route::resource('grupos', GrupoController::class)
            ->parameters(['grupos' => 'grupo'])
            ->except(['destroy']);

        Route::resource('aulas', AulaController::class)
            ->parameters(['aulas' => 'aula'])
            ->only(['index', 'store', 'edit', 'update']);

        Route::resource('matriculas', MatriculaController::class)
            ->parameters(['matriculas' => 'matricula'])
            ->only(['index', 'create', 'store', 'show']);
        Route::patch('matriculas/{matricula}/estado', [MatriculaController::class, 'cambiarEstado'])->name('matriculas.estado');
        Route::patch('comprobantes/{comprobante}/aprobar', [ComprobanteController::class, 'aprobar'])->name('comprobantes.aprobar');
        Route::patch('comprobantes/{comprobante}/rechazar', [ComprobanteController::class, 'rechazar'])->name('comprobantes.rechazar');

        Route::resource('clientes-api', ClienteApiController::class)
            ->parameters(['clientes-api' => 'cliente'])
            ->only(['index', 'store', 'destroy']);
    });
});
