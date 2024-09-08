<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\EtiquetaController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\LoteController;
use App\Http\Controllers\ComprobanteController;


Route::post('/login', [LoginController::class, 'login']);  // Ruta para el login
Route::post('/register', [UsuarioController::class, 'register']);  // Ruta para el registro de usuarios
Route::get('/usuarios', [UsuarioController::class, 'index']);  // Ruta para listar usuarios
Route::put('/usuarios/{id}', [UsuarioController::class, 'update']);  // Ruta para actualizar usuario




Route::middleware('auth:api')->group(function () {
    Route::get('/etiquetas', [EtiquetaController::class, 'index']);
    Route::get('/etiquetas/{id}', [EtiquetaController::class, 'show']);
    Route::post('/etiquetas', [EtiquetaController::class, 'store']);
    Route::put('/etiquetas/{id}', [EtiquetaController::class, 'update']);
    Route::get('/proveedores', [ProveedorController::class, 'index']);
    Route::get('/proveedores/{id}', [ProveedorController::class, 'show']);
    Route::post('/proveedor', [ProveedorController::class, 'store']);
    //rutas de lotes
    Route::get('/lotes', [LoteController::class, 'index']);
    Route::get('/lotes/{id}', [LoteController::class, 'show']);
    Route::post('/lotes', [LoteController::class, 'store']);
});


Route::get('/comprobantes', [ComprobanteController::class, 'index']);  // Listar todos los comprobantes
Route::get('/comprobantes/{id}', [ComprobanteController::class, 'show']);  // Mostrar un comprobante específico
Route::post('/comprobantes', [ComprobanteController::class, 'store']);
Route::get('/productos', [ProductoController::class, 'index']);
Route::get('/productos/{id}', [ProductoController::class, 'show']);
Route::post('/productos', [ProductoController::class, 'store']);