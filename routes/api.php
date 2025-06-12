<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TelefonoUsuarioController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\MantenimientoController;
use App\Http\Controllers\DetalleMantenimientoController;
use App\Http\Controllers\AdminController;

Route::prefix('v1')->group(function () {
    
    // Rutas públicas
    Route::post('/user/login', [UserController::class, 'login']);
    Route::post('/user/signup', [UserController::class, 'store']);

    // Rutas protegidas por JWT
    Route::middleware('auth.jwt')->group(function () {

        /* RUTAS USER */
        Route::post('/user/add', [UserController::class, 'store']);
        Route::get('/user/getUsers', [UserController::class, 'index']);
        Route::get('/user/getUser/{email}', [UserController::class, 'show']);
        Route::get('/user/getidentity', [UserController::class, 'getIdentity']);
        Route::put('/user/updateUser/{email}', [UserController::class, 'update']);
        Route::delete('/user/destroyUser/{email}', [UserController::class, 'destroy']);

        /* RUTAS ADMIN */
        Route::get('/admin', [AdminController::class, 'index']);

        /* RUTAS TELEFONO USUARIO */
        Route::get('/telefonousuario', [TelefonoUsuarioController::class, 'index']);
        Route::post('/telefonousuario', [TelefonoUsuarioController::class, 'store']);
        Route::get('/telefonousuario/{id}', [TelefonoUsuarioController::class, 'show']);
        Route::put('/telefonousuario/{id}', [TelefonoUsuarioController::class, 'update']);
        Route::delete('/telefonousuario/{id}', [TelefonoUsuarioController::class, 'destroy']);

        /* RUTAS EQUIPO */
        Route::get('/equipo', [EquipoController::class, 'index']);
        Route::post('/equipo', [EquipoController::class, 'store']);
        Route::get('/equipo/{id}', [EquipoController::class, 'show']);
        Route::put('/equipo/{id}', [EquipoController::class, 'update']);
        Route::delete('/equipo/{id}', [EquipoController::class, 'destroy']);

        /* RUTAS MANTENIMIENTO */
        Route::get('/mantenimiento', [MantenimientoController::class, 'index']);
        Route::post('/mantenimiento', [MantenimientoController::class, 'store']);
        Route::get('/mantenimiento/{id}', [MantenimientoController::class, 'show']);
        Route::put('/mantenimiento/{id}', [MantenimientoController::class, 'update']);
        Route::delete('/mantenimiento/{id}', [MantenimientoController::class, 'destroy']);

        /* RUTAS DETALLE MANTENIMIENTO */
        Route::get('/detallemantenimiento', [DetalleMantenimientoController::class, 'index']);
        Route::post('/detallemantenimiento', [DetalleMantenimientoController::class, 'store']);
        Route::get('/detallemantenimiento/{id}', [DetalleMantenimientoController::class, 'show']);
        Route::put('/detallemantenimiento/{id}', [DetalleMantenimientoController::class, 'update']);
        Route::delete('/detallemantenimiento/{id}', [DetalleMantenimientoController::class, 'destroy']);
    });
});
