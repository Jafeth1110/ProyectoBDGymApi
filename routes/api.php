<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Middleware\ApiAuthMiddleware;

Route::prefix('v1')->group(function () {
    // Rutas específicas
    
    /* RUTAS USER */
    Route::post('/user/login', [UserController::class,'login']);
    Route::post('/user/signup', [UserController::class,'store']); //RUTA SINGUP DONDE SE EJECUTA EL METODO STORE
    Route::post('/user/add', [UserController::class,'store'])/*->middleware(ApiAuthMiddleware::class)*/;
    Route::get('/user/getUsers', [UserController::class, 'index'])/*->middleware(ApiAuthMiddleware::class)*/;
    Route::get('/user/getUser/{email}', [UserController::class, 'show'])/*->middleware(ApiAuthMiddleware::class)*/;
    Route::get('/user/getidentity', [UserController::class,'getIdentity'])/*->middleware(ApiAuthMiddleware::class)*/;
    Route::put('/user/updateUser/{email}', [UserController::class, 'update'])/*->middleware(ApiAuthMiddleware::class)*/;
    Route::delete('/user/destroyUser/{email}', [UserController::class, 'destroy'])/*->middleware(ApiAuthMiddleware::class)*/;
    
});

