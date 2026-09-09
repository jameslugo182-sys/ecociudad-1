<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CamionController;
use App\Http\Controllers\ContratoController;
use App\Http\Controllers\CuentaColaboradorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GestionRutasController;
use App\Http\Controllers\LimpiezaController;
use App\Http\Controllers\MaestroContratoController;
use App\Http\Controllers\MapaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ReporteMetricasController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('role:ciudadano')->group(function () {
        Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('/reportes/crear', [ReporteController::class, 'create'])->name('reportes.create');
        Route::post('/reportes', [ReporteController::class, 'store'])->name('reportes.store');
    });

    Route::prefix('administracion')
        ->name('administracion.')
        ->group(function () {
            Route::get('/', DashboardController::class)
                ->middleware('role:administrador')
                ->name('dashboard');

            Route::middleware('module:incidencias')->group(function () {
                Route::get('/incidencias', [AdminController::class, 'dashboard'])->name('incidencias.index');
                Route::patch('/incidencias/{reporte}/estado', [AdminController::class, 'actualizarEstado'])
                    ->name('incidencias.estado');
                Route::patch('/incidencias/{reporte}/asignar', [AdminController::class, 'asignar'])
                    ->name('incidencias.asignar');
            });

            Route::middleware('module:contratos')->group(function () {
                Route::get('/contratos', [ContratoController::class, 'index'])->name('contratos.index');
                Route::post('/contratos', [ContratoController::class, 'store'])->name('contratos.store');
                Route::post('/contratos/{contrato}', [ContratoController::class, 'update'])
                    ->whereNumber('contrato')
                    ->name('contratos.update');

                Route::get('/contratos/usuarios', [CuentaColaboradorController::class, 'index'])
                    ->name('contratos.usuarios.index');
                Route::post('/contratos/usuarios', [CuentaColaboradorController::class, 'store'])
                    ->name('contratos.usuarios.store');
                Route::put('/contratos/usuarios/{user}', [CuentaColaboradorController::class, 'update'])
                    ->name('contratos.usuarios.update');
                Route::patch('/contratos/usuarios/{user}/estado', [CuentaColaboradorController::class, 'cambiarEstado'])
                    ->name('contratos.usuarios.estado');

                Route::get('/contratos/maestros', [MaestroContratoController::class, 'index'])
                    ->name('contratos.maestros.index');
                Route::get('/contratos/maestros/cargos', [MaestroContratoController::class, 'cargos'])
                    ->name('contratos.maestros.cargos.index');
                Route::get('/contratos/maestros/roles', [MaestroContratoController::class, 'roles'])
                    ->name('contratos.maestros.roles.index');
                Route::post('/contratos/maestros/cargos', [MaestroContratoController::class, 'storeCargo'])
                    ->name('contratos.maestros.cargos.store');
                Route::put('/contratos/maestros/cargos/{cargo}', [MaestroContratoController::class, 'updateCargo'])
                    ->name('contratos.maestros.cargos.update');
                Route::patch('/contratos/maestros/cargos/{cargo}/estado', [MaestroContratoController::class, 'cambiarEstadoCargo'])
                    ->name('contratos.maestros.cargos.estado');
                Route::post('/contratos/maestros/roles', [MaestroContratoController::class, 'storeRol'])
                    ->name('contratos.maestros.roles.store');
                Route::put('/contratos/maestros/roles/{rol}', [MaestroContratoController::class, 'updateRol'])
                    ->name('contratos.maestros.roles.update');
                Route::patch('/contratos/maestros/roles/{rol}/estado', [MaestroContratoController::class, 'cambiarEstadoRol'])
                    ->name('contratos.maestros.roles.estado');
            });

            Route::middleware('module:vehiculos')->group(function () {
                Route::get('/vehiculos', [CamionController::class, 'index'])->name('vehiculos.index');
                Route::post('/vehiculos', [CamionController::class, 'store'])->name('vehiculos.store');
                Route::put('/vehiculos/{camion}', [CamionController::class, 'update'])->name('vehiculos.update');
                Route::delete('/vehiculos/{camion}', [CamionController::class, 'destroy'])->name('vehiculos.destroy');
                Route::put('/vehiculos/{camion}/equipo', [CamionController::class, 'asignarEquipo'])
                    ->name('vehiculos.equipo');
            });

            Route::get('/rutas', [GestionRutasController::class, 'index'])
                ->middleware('module:rutas')
                ->name('rutas.index');
            Route::post('/rutas', [GestionRutasController::class, 'store'])
                ->middleware('module:rutas')
                ->name('rutas.store');
            Route::patch('/rutas/{ruta}/cancelar', [GestionRutasController::class, 'cancelar'])
                ->middleware('module:rutas')
                ->name('rutas.cancelar');
            Route::get('/reportes', [ReporteMetricasController::class, 'index'])
                ->middleware('module:reportes')
                ->name('reportes.index');
        });

    Route::prefix('admin')
        ->name('admin.')
        ->middleware('role:personal_municipal')
        ->group(function () {
            Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
            Route::get('/reportes', [AdminController::class, 'dashboard'])->name('reportes.index');
            Route::get('/mapa', [MapaController::class, 'index'])->name('mapa');
            Route::patch('/reportes/{reporte}/estado', [AdminController::class, 'actualizarEstado'])
                ->name('reportes.estado');
            Route::patch('/reportes/{reporte}/asignar', [AdminController::class, 'asignar'])
                ->name('reportes.asignar');
            Route::post('/rutas/generar', [AdminController::class, 'generarRuta'])
                ->name('rutas.generar');
            Route::get('/rutas', [GestionRutasController::class, 'index'])->name('rutas.index');
            Route::post('/rutas', [GestionRutasController::class, 'store'])->name('rutas.store');
            Route::patch('/rutas/{ruta}/cancelar', [GestionRutasController::class, 'cancelar'])
                ->name('rutas.cancelar');
            Route::get('/metricas', [ReporteMetricasController::class, 'index'])->name('metricas.index');
        });

    Route::prefix('limpieza')
        ->name('limpieza.')
        ->middleware('module:operaciones')
        ->group(function () {
            Route::get('/', [LimpiezaController::class, 'dashboard'])->name('dashboard');
            Route::get('/rutas', [LimpiezaController::class, 'dashboard'])->name('rutas.index');
            Route::patch('/reportes/{reporte}/iniciar', [LimpiezaController::class, 'iniciar'])
                ->name('reportes.iniciar');
            Route::post('/reportes/{reporte}/evidencia', [LimpiezaController::class, 'registrarEvidencia'])
                ->name('reportes.evidencia');
        });
});

require __DIR__.'/auth.php';
