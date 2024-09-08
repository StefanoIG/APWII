<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\EtiquetaController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\LoteController;
use App\Http\Controllers\ComprobanteController;
use App\Http\Controllers\RetornoController;
use App\Http\Controllers\SitioController;


//requets demo
Route::post('/demo', [UsuarioController::class, 'requestDemo']);
Route::post('/demo/approve/', [UsuarioController::class, 'approveDemo']);
Route::post('/demo/reject/', [UsuarioController::class, 'rejectDemo']);


//rutas de autenticación
Route::post('/login', [LoginController::class, 'login']);  // Ruta para el login
Route::post('/register', [UsuarioController::class, 'register']);  // Ruta para el registro de usuarios

//recuperar contra
Route::post('/forget', [UsuarioController::class, 'recoveryPassword']);
Route::post('/reset-password', [UsuarioController::class, 'resetPassword']);


Route::middleware('auth:api')->group(function () {
    //rutas de usuarios 
    Route::get('/usuarios', [UsuarioController::class, 'index']);  // Ruta para listar usuarios
    Route::put('/usuarios/{id}', [UsuarioController::class, 'update']);  // Ruta para actualizar usuario

    Route::get('/etiquetas', [EtiquetaController::class, 'index']);
    Route::get('/etiquetas/{id}', [EtiquetaController::class, 'show']);
    Route::post('/etiquetas', [EtiquetaController::class, 'store']);
    Route::put('/etiquetas/{id}', [EtiquetaController::class, 'update']);

    //rutas de proveedores
    Route::get('/proveedores', [ProveedorController::class, 'index']);
    Route::get('/proveedores/{id}', [ProveedorController::class, 'show']);
    Route::post('/proveedor', [ProveedorController::class, 'store']);


    //rutas de lotes
    Route::get('/lotes', [LoteController::class, 'index']);
    Route::get('/lotes/{id}', [LoteController::class, 'show']);
    Route::post('/lotes', [LoteController::class, 'store']);

    //rutas de comprobantes
    Route::get('/comprobantes', [ComprobanteController::class, 'index']);  // Listar todos los comprobantes
    Route::get('/comprobantes/{id}', [ComprobanteController::class, 'show']);  // Mostrar un comprobante específico
    Route::post('/comprobantes', [ComprobanteController::class, 'store']);
    Route::get('/comprobante/{id}/pdf', [ComprobanteController::class, 'generarPDF']);

    //rutas de productos
    Route::get('/productos', [ProductoController::class, 'index']);
    Route::get('/productos/{id}', [ProductoController::class, 'show']);
    Route::post('/productos', [ProductoController::class, 'store']);

    //rutas de retorno
    Route::get('/retornos', [RetornoController::class, 'index']);
    Route::get('/retornos/{id}', [RetornoController::class, 'show']);
    Route::post('/retornos', [RetornoController::class, 'store']);

    //rutas barcodes
    Route::get('producto/{id}/barcode', [ProductoController::class, 'verCodigoDeBarras']);
    Route::get('lote/{id}/barcode', [LoteController::class, 'verCodigoDeBarras']);


    //rutas de sitios
    Route::get('/sitios', [SitioController::class, 'index']);
    Route::get('/sitios/{id}', [SitioController::class, 'show']);
    Route::post('/sitios', [SitioController::class, 'store']);
    Route::put('/sitios/{id}', [SitioController::class, 'update']);
    Route::delete('/sitios/{id}', [SitioController::class, 'destroy']);

    Route::get('/comprobantes/sitio/{id}', [ComprobanteController::class, 'obtenerComprobantesPorSitio']);
});
