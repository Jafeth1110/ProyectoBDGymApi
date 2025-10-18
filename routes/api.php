<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TelefonoController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\MantenimientoController;
use App\Http\Controllers\DetalleMantenimientoController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\EntrenadorController;
use App\Http\Controllers\ClaseController;
use App\Http\Controllers\InscripcionClaseController;
use App\Http\Controllers\MembresiaController;
use App\Http\Controllers\MetodoPagoController;
use App\Http\Controllers\PagoController;

Route::prefix('v1')->group(function () {
    
    // Rutas públicas
    Route::post('/user/login', [UserController::class, 'login']);
    Route::post('/user/signup', [UserController::class, 'store']);
    Route::post('/user/register', [UserController::class, 'store']); // Alias para registro público
    Route::post('/user/add', [UserController::class, 'store']); // Para compatibilidad con frontend

    // Rutas protegidas por JWT
    Route::middleware('auth.jwt')->group(function () {

        /* RUTAS TELEFONOS GENERALES (Nueva tabla unificada) */
        Route::get('/telefonos', [TelefonoController::class, 'index']);
        Route::post('/telefonos', [TelefonoController::class, 'store']);
        Route::get('/telefonos/{id}', [TelefonoController::class, 'show']);
        Route::put('/telefonos/{id}', [TelefonoController::class, 'update']);
        Route::delete('/telefonos/{id}', [TelefonoController::class, 'destroy']);
        Route::get('/telefonos/user/{userId}', [TelefonoController::class, 'getByUser']);
        Route::get('/telefonos/role/{rolId}', [TelefonoController::class, 'getByRole']);

        /* RUTAS TELÉFONOS DE USUARIO (métodos del UserController para compatibilidad) */
        Route::post('/user/{email}/telefono', [UserController::class, 'addTelefono']);
        Route::get('/user/{email}/telefonos', [UserController::class, 'getTelefonos']);
        Route::put('/user/{email}/telefonos', [UserController::class, 'updateTelefonos']);
        Route::delete('/user/{email}/telefonos', [UserController::class, 'clearTelefonos']);

        /* RUTAS USER */
        Route::get('/user/getidentity', [UserController::class, 'getIdentity']);
        Route::get('/user/getUsers', [UserController::class, 'index']);
        Route::get('/user/getUser/{email}', [UserController::class, 'show']);
        Route::get('/user/verify/{email}', [UserController::class, 'verifyUserData']); // Nueva ruta para verificar
        Route::put('/user/updateUser/{email}', [UserController::class, 'update']);
        Route::delete('/user/destroyUser/{email}', [UserController::class, 'destroy']);
        Route::post('/logout', [UserController::class, 'logout']);



        /* RUTAS ADMIN */
        Route::get('/admin', [AdminController::class, 'index']);
        Route::post('/admin', [AdminController::class, 'store']);
        Route::get('/admin/{id}', [AdminController::class, 'show']);
        Route::put('/admin/{id}', [AdminController::class, 'update']);
        Route::delete('/admin/{id}', [AdminController::class, 'destroy']);

        /* RUTAS CLIENTE */
        Route::get('/cliente', [ClienteController::class, 'index']);
        Route::post('/cliente', [ClienteController::class, 'store']);
        Route::get('/cliente/{id}', [ClienteController::class, 'show']);
        Route::put('/cliente/{id}', [ClienteController::class, 'update']);
        Route::delete('/cliente/{id}', [ClienteController::class, 'destroy']);

        /* RUTAS ENTRENADOR */
        Route::get('/entrenador', [EntrenadorController::class, 'index']);
        Route::post('/entrenador', [EntrenadorController::class, 'store']);
        Route::get('/entrenador/{id}', [EntrenadorController::class, 'show']);
        Route::put('/entrenador/{id}', [EntrenadorController::class, 'update']);
        Route::delete('/entrenador/{id}', [EntrenadorController::class, 'destroy']);

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
        Route::get('/mantenimiento/current-admin', [MantenimientoController::class, 'getCurrentAdmin']);

        /* RUTAS DETALLE MANTENIMIENTO */
        Route::get('/detallemantenimiento', [DetalleMantenimientoController::class, 'index']);
        Route::post('/detallemantenimiento', [DetalleMantenimientoController::class, 'store']);
        Route::get('/detallemantenimiento/{id}', [DetalleMantenimientoController::class, 'show']);
        Route::put('/detallemantenimiento/{id}', [DetalleMantenimientoController::class, 'update']);
        Route::delete('/detallemantenimiento/{id}', [DetalleMantenimientoController::class, 'destroy']);

        /* RUTAS CLASES */
        Route::get('/clases', [ClaseController::class, 'index']);
        Route::post('/clases', [ClaseController::class, 'store']);
        Route::get('/clases/{id}', [ClaseController::class, 'show']);
        Route::put('/clases/{id}', [ClaseController::class, 'update']);
        Route::delete('/clases/{id}', [ClaseController::class, 'destroy']);

        /* RUTAS INSCRIPCIONES CLASES */
        Route::get('/inscripcionclase', [InscripcionClaseController::class, 'index']);
        Route::post('/inscripcionclase', [InscripcionClaseController::class, 'store']);
        Route::get('/inscripcionclase/{id}', [InscripcionClaseController::class, 'show']);
        Route::put('/inscripcionclase/{id}', [InscripcionClaseController::class, 'update']);
        Route::delete('/inscripcionclase/{id}', [InscripcionClaseController::class, 'destroy']);

        /* RUTAS MEMBRESIAS */
        Route::get('/membresias', [MembresiaController::class, 'index']);
        Route::post('/membresias', [MembresiaController::class, 'store']);
        
        // Rutas para plantillas (admin)
        Route::post('/membresias/plantillas', [MembresiaController::class, 'createPlantilla']);
        Route::get('/membresias/plantillas', [MembresiaController::class, 'getPlantillas']);
        
        // Rutas para asignar membresías a clientes
        Route::post('/membresias/asignar', [MembresiaController::class, 'asignarMembresiaCliente']);
        Route::get('/membresias/cliente/{idCliente}/membresias', [MembresiaController::class, 'getMembresiasCliente']);
        
        // Rutas para estadísticas
        Route::get('/membresias/estadisticas', [MembresiaController::class, 'getEstadisticas']);
        
        // Rutas existentes
        Route::get('/membresias/activas', [MembresiaController::class, 'getActivas']);
        Route::get('/membresias/vencidas', [MembresiaController::class, 'getVencidas']);
        Route::post('/membresias/update-estados', [MembresiaController::class, 'updateEstados']);
        Route::get('/membresias/cliente/{idCliente}', [MembresiaController::class, 'getByCliente']);
        Route::get('/membresias/{id}', [MembresiaController::class, 'show']);
        Route::put('/membresias/{id}', [MembresiaController::class, 'update']);
        Route::delete('/membresias/{id}', [MembresiaController::class, 'destroy']);

        /* RUTAS MÉTODOS DE PAGO */
        Route::get('/metodospago', [MetodoPagoController::class, 'index']);
        Route::post('/metodospago', [MetodoPagoController::class, 'store']);
        Route::get('/metodospago/{id}', [MetodoPagoController::class, 'show']);
        Route::put('/metodospago/{id}', [MetodoPagoController::class, 'update']);
        Route::delete('/metodospago/{id}', [MetodoPagoController::class, 'destroy']);

        /* RUTAS PAGOS */
        Route::get('/pagos', [PagoController::class, 'index']);
        Route::post('/pagos', [PagoController::class, 'store']);
        Route::get('/pagos/{id}', [PagoController::class, 'show']);
        Route::put('/pagos/{id}', [PagoController::class, 'update']);
        Route::delete('/pagos/{id}', [PagoController::class, 'destroy']);
    });
});
