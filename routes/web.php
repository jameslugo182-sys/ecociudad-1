<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CamionController;
use App\Http\Controllers\ContratoController;
use App\Http\Controllers\CuentaColaboradorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GestionRutasController;
use App\Http\Controllers\HorarioPersonalController;
use App\Http\Controllers\LimpiezaController;
use App\Http\Controllers\MaestroContratoController;
use App\Http\Controllers\MantenimientoVehiculoController;
use App\Http\Controllers\MapaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ReporteMetricasController;
use App\Http\Controllers\TipoMantenimientoController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'heroImage' => asset('images/ecociudad-hero-3d.png'),
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
                Route::get('/incidencias/mapa', [MapaController::class, 'index'])->name('incidencias.mapa');
                Route::patch('/incidencias/{reporte}/estado', [AdminController::class, 'actualizarEstado'])
                    ->name('incidencias.estado');
                Route::patch('/incidencias/{reporte}/asignar', [AdminController::class, 'asignar'])
                    ->name('incidencias.asignar');
            });

            Route::middleware('module:contratos')->group(function () {
                Route::get('/contratos', [ContratoController::class, 'index'])->name('contratos.index');
                Route::post('/contratos', [ContratoController::class, 'store'])->name('contratos.store');
                Route::post('/contratos/formato', [ContratoController::class, 'formato'])->name('contratos.formato');
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

                Route::get('/contratos/horarios/plantillas', [HorarioPersonalController::class, 'plantillas'])
                    ->name('contratos.horarios.plantillas');
                Route::post('/contratos/horarios/plantillas', [HorarioPersonalController::class, 'storePlantilla'])
                    ->name('contratos.horarios.plantillas.store');
                Route::delete('/contratos/horarios/plantillas/{plantilla}', [HorarioPersonalController::class, 'destroyPlantilla'])
                    ->name('contratos.horarios.plantillas.destroy');
                Route::get('/contratos/horarios', [HorarioPersonalController::class, 'registro'])
                    ->name('contratos.horarios.registro');
                Route::post('/contratos/horarios', [HorarioPersonalController::class, 'storeRegistro'])
                    ->name('contratos.horarios.registro.store');
                Route::get('/contratos/horarios/{horario}/pdf', [HorarioPersonalController::class, 'pdf'])
                    ->whereNumber('horario')
                    ->name('contratos.horarios.registro.pdf');

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
                Route::get('/contratos/maestros/areas', [MaestroContratoController::class, 'areas'])
                    ->name('contratos.maestros.areas.index');
                Route::post('/contratos/maestros/areas', [MaestroContratoController::class, 'storeArea'])
                    ->name('contratos.maestros.areas.store');
                Route::put('/contratos/maestros/areas/{area}', [MaestroContratoController::class, 'updateArea'])
                    ->name('contratos.maestros.areas.update');
                Route::patch('/contratos/maestros/areas/{area}/estado', [MaestroContratoController::class, 'cambiarEstadoArea'])
                    ->name('contratos.maestros.areas.estado');
                Route::get('/contratos/maestros/sedes', [MaestroContratoController::class, 'sedes'])
                    ->name('contratos.maestros.sedes.index');
                Route::post('/contratos/maestros/sedes', [MaestroContratoController::class, 'storeSede'])
                    ->name('contratos.maestros.sedes.store');
                Route::put('/contratos/maestros/sedes/{sede}', [MaestroContratoController::class, 'updateSede'])
                    ->name('contratos.maestros.sedes.update');
                Route::patch('/contratos/maestros/sedes/{sede}/estado', [MaestroContratoController::class, 'cambiarEstadoSede'])
                    ->name('contratos.maestros.sedes.estado');
            });

            Route::middleware('module:vehiculos')->group(function () {
                Route::get('/vehiculos', [CamionController::class, 'index'])->name('vehiculos.index');
                Route::get('/vehiculos/equipos', [CamionController::class, 'equipos'])->name('vehiculos.equipos');
                Route::post('/vehiculos', [CamionController::class, 'store'])->name('vehiculos.store');

                Route::get('/vehiculos/mantenimiento', [MantenimientoVehiculoController::class, 'registro'])
                    ->name('vehiculos.mantenimiento.registro');
                Route::post('/vehiculos/mantenimiento', [MantenimientoVehiculoController::class, 'store'])
                    ->name('vehiculos.mantenimiento.store');
                Route::get('/vehiculos/mantenimiento/en-curso', [MantenimientoVehiculoController::class, 'enCurso'])
                    ->name('vehiculos.mantenimiento.en-curso');
                Route::get('/vehiculos/mantenimiento/historial', [MantenimientoVehiculoController::class, 'historial'])
                    ->name('vehiculos.mantenimiento.historial');
                Route::patch('/vehiculos/mantenimiento/{mantenimiento}/finalizar', [MantenimientoVehiculoController::class, 'finalizar'])
                    ->name('vehiculos.mantenimiento.finalizar');

                Route::get('/vehiculos/maestros/tipos', [TipoMantenimientoController::class, 'index'])
                    ->name('vehiculos.maestros.tipos.index');
                Route::post('/vehiculos/maestros/tipos', [TipoMantenimientoController::class, 'store'])
                    ->name('vehiculos.maestros.tipos.store');
                Route::put('/vehiculos/maestros/tipos/{tipo}', [TipoMantenimientoController::class, 'update'])
                    ->name('vehiculos.maestros.tipos.update');
                Route::patch('/vehiculos/maestros/tipos/{tipo}/estado', [TipoMantenimientoController::class, 'cambiarEstado'])
                    ->name('vehiculos.maestros.tipos.estado');

                Route::post('/vehiculos/{camion}', [CamionController::class, 'update'])
                    ->whereNumber('camion')
                    ->name('vehiculos.update');
                Route::delete('/vehiculos/{camion}', [CamionController::class, 'destroy'])
                    ->whereNumber('camion')
                    ->name('vehiculos.destroy');
                Route::put('/vehiculos/{camion}/equipo', [CamionController::class, 'asignarEquipo'])
                    ->whereNumber('camion')
                    ->name('vehiculos.equipo');
            });

            Route::get('/rutas', [GestionRutasController::class, 'index'])
                ->middleware('module:rutas')
                ->name('rutas.index');
            Route::get('/rutas/horarios', [GestionRutasController::class, 'horarios'])
                ->middleware('module:rutas')
                ->name('rutas.horarios');
            Route::get('/rutas/cronograma', [GestionRutasController::class, 'cronograma'])
                ->middleware('module:rutas')
                ->name('rutas.cronograma');
            Route::post('/rutas', [GestionRutasController::class, 'store'])
                ->middleware('module:rutas')
                ->name('rutas.store');
            Route::post('/rutas/proponer', [GestionRutasController::class, 'proponer'])
                ->middleware('module:rutas')
                ->name('rutas.proponer');
            Route::patch('/rutas/{ruta}/horario', [GestionRutasController::class, 'actualizarHorario'])
                ->middleware('module:rutas')
                ->name('rutas.horario');
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
            Route::get('/rutas/horarios', [GestionRutasController::class, 'horarios'])->name('rutas.horarios');
            Route::get('/rutas/cronograma', [GestionRutasController::class, 'cronograma'])->name('rutas.cronograma');
            Route::post('/rutas', [GestionRutasController::class, 'store'])->name('rutas.store');
            Route::post('/rutas/proponer', [GestionRutasController::class, 'proponer'])->name('rutas.proponer');
            Route::patch('/rutas/{ruta}/horario', [GestionRutasController::class, 'actualizarHorario'])
                ->name('rutas.horario');
            Route::patch('/rutas/{ruta}/cancelar', [GestionRutasController::class, 'cancelar'])
                ->name('rutas.cancelar');
            Route::get('/metricas', [ReporteMetricasController::class, 'index'])->name('metricas.index');
        });

    Route::prefix('limpieza')
        ->name('limpieza.')
        ->middleware('module:operaciones')
        ->group(function () {
            Route::get('/', [LimpiezaController::class, 'dashboard'])->name('dashboard');
            Route::get('/rutas', [LimpiezaController::class, 'recorrido'])->name('rutas.index');
            Route::patch('/reportes/{reporte}/iniciar', [LimpiezaController::class, 'iniciar'])
                ->name('reportes.iniciar');
            Route::post('/reportes/{reporte}/evidencia', [LimpiezaController::class, 'registrarEvidencia'])
                ->name('reportes.evidencia');
        });
});

require __DIR__.'/auth.php';
