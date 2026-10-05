<?php

use App\Http\Controllers\AulaController;
use App\Http\Controllers\CalendarioController;
use App\Http\Controllers\CohorteController;
use App\Http\Controllers\GrupoController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MatriculaController;
use App\Http\Controllers\MisCursosController;
use App\Http\Controllers\MisGruposController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\ProgramaController;
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
    });
});
