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
use App\Http\Controllers\ChatBotController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\PromocionController;
use App\Http\Controllers\MetodoPagoController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\UsuarioRolController;
use App\Http\Controllers\RolPermisoController;

Route::prefix('roles')->group(function() {
    Route::get('/', [RolController::class, 'index']);  // Obtener todos los roles
    Route::post('/', [RolController::class, 'store']); // Crear un rol
    Route::get('{id}', [RolController::class, 'show']); // Mostrar un rol específico
    Route::put('{id}', [RolController::class, 'update']); // Actualizar un rol
    Route::delete('{id}', [RolController::class, 'destroy']); // Eliminar un rol
});

Route::prefix('permisos')->group(function() {
    Route::get('/', [PermisoController::class, 'index']);  // Obtener todos los permisos
    Route::post('/', [PermisoController::class, 'store']); // Crear un permiso
    Route::get('{id}', [PermisoController::class, 'show']); // Mostrar un permiso específico
    Route::put('{id}', [PermisoController::class, 'update']); // Actualizar un permiso
    Route::delete('{id}', [PermisoController::class, 'destroy']); // Eliminar un permiso
});

Route::prefix('usuario_rol')->group(function() {
    Route::post('/', [UsuarioRolController::class, 'store']);  // Asignar un rol a un usuario
    Route::delete('/', [UsuarioRolController::class, 'destroy']); // Remover un rol de un usuario
    Route::get('{usuario_id}', [UsuarioRolController::class, 'show']); // Obtener roles de un usuario
});

Route::prefix('rol_permiso')->group(function() {
    Route::post('/', [RolPermisoController::class, 'store']);  // Asignar un permiso a un rol
    Route::delete('/', [RolPermisoController::class, 'destroy']); // Remover un permiso de un rol
    Route::get('{rol_id}', [RolPermisoController::class, 'show']); // Obtener permisos de un rol
});


//rutas de paypal publicas
Route::get('/payment/success', [UsuarioController::class, 'paymentSuccess'])->name('paypal.payment.success');
Route::get('/payment/cancel', [UsuarioController::class, 'paymentCancel'])->name('paypal.payment.cancel');
Route::get('/payment/failure', [UsuarioController::class, 'paymentFailure'])->name('paypal.payment.failure');

Route::get('/planes', [PlanController::class, 'index']);

//rutas chatbot
Route::post('/chat', [ChatBotController::class, 'chat']); // Ruta para el chat

//por ahora dejarla aqui
Route::post('/sitios', [SitioController::class, 'store']);

//rutas de autenticación
Route::post('/login', [LoginController::class, 'login']);  // Ruta para el login
Route::post('/register', [UsuarioController::class, 'register']);  // Ruta para el registro de usuarios

//recuperar contra
Route::post('/forget', [UsuarioController::class, 'recoveryPassword']);
Route::post('/reset-password', [UsuarioController::class, 'resetPassword']);

//requets demo
Route::post('/demo', [UsuarioController::class, 'requestDemo']);


Route::get('/proveedores-pagination', [ProveedorController::class, 'paginatedIndex']);
Route::post('/proveedor', [ProveedorController::class, 'store']);



//rutas protegidas
Route::middleware('auth:api')->group(function () {

    //rutas chatbot
    Route::post('/questions', [ChatBotController::class, 'store']); // Ruta para almacenar preguntas y respuestas
    Route::get('/questions', [ChatBotController::class, 'getAllQuestions']); // Ruta para obtener todas las preguntas con paginación


    //rutas de usuarios 
    Route::get('/usuarios', [UsuarioController::class, 'index']);  // Ruta para listar usuarios
    Route::put('/usuarios/{id}', [UsuarioController::class, 'update']);  // Ruta para actualizar usuario


    //rutas de demo
    Route::post('/demo/approve/', [UsuarioController::class, 'approveDemo']);
    Route::post('/demo/reject/', [UsuarioController::class, 'rejectDemo']);


    //rutas de etiquetas
    Route::get('/etiquetas', [EtiquetaController::class, 'index']);
    Route::get('/etiquetas/{id}', [EtiquetaController::class, 'show']);
    Route::post('/etiquetas', [EtiquetaController::class, 'store']);
    Route::put('/etiquetas/{id}', [EtiquetaController::class, 'update']);

    //rutas de proveedores
    Route::get('/proveedores', [ProveedorController::class, 'index']);
    Route::get('/proveedores/{id}', [ProveedorController::class, 'show']);
    Route::put('/proveedores/{id}', [ProveedorController::class, 'update']);
    Route::delete('/proveedores/{id}', [ProveedorController::class, 'destroy']);


    //rutas de lotes
    Route::get('/lotes', [LoteController::class, 'index']);
    Route::get('/lotes/{id}', [LoteController::class, 'show']);
    Route::post('/lotes', [LoteController::class, 'store']);
    Route::delete('/lotes/{id}', [LoteController::class, 'destroy']);


    //rutas de comprobantes
    Route::get('/comprobantes', [ComprobanteController::class, 'index']);  // Listar todos los comprobantes
    Route::get('/comprobantes/{id}', [ComprobanteController::class, 'show']);  // Mostrar un comprobante específico
    Route::post('/comprobantes', [ComprobanteController::class, 'store']);
    Route::get('/comprobante/{id}/pdf', [ComprobanteController::class, 'generarPDF']);
    Route::get('/comprobantes/sitio/{id}', [ComprobanteController::class, 'obtenerComprobantesPorSitio']);


    //rutas de productos
    Route::get('/productos/{id}', [ProductoController::class, 'show']);
    Route::get('/productos', [ProductoController::class, 'index']);
    Route::post('/productos', [ProductoController::class, 'store']);

    //rutas de retorno
    Route::get('/retornos', [RetornoController::class, 'index']);
    Route::get('/retornos/{id}', [RetornoController::class, 'show']);
    Route::post('/retornos', [RetornoController::class, 'store']);;


    //rutas de sitios
    Route::get('/sitios', [SitioController::class, 'index']);
    Route::get('/sitios/{id}', [SitioController::class, 'show']);
    Route::put('/sitios/{id}', [SitioController::class, 'update']);
    Route::delete('/sitios/{id}', [SitioController::class, 'destroy']);



    //rutas barcodes
    Route::get('producto/{id}/barcode', [ProductoController::class, 'verCodigoDeBarras']);
    Route::get('lote/{id}/barcode', [LoteController::class, 'verCodigoDeBarras']);
    Route::post('/BC-Lote', [LoteController::class, 'showByCodigoLote']);

    //rutas de paginacion
    Route::get('/productos-pagination', [ProductoController::class, 'paginatedIndex']);
    Route::get('/lotes-pagination', [LoteController::class, 'paginatedIndex']);
    Route::get('/sitios-pagination', [SitioController::class, 'paginatedIndex']);
    Route::get('/usuarios-pagination', [UsuarioController::class, 'paginatedIndex']);
    Route::get('/etiquetas-pagination', [EtiquetaController::class, 'paginatedIndex']);
    Route::get('/comprobantes-pagination', [ComprobanteController::class, 'paginatedIndex']);
    Route::get('/retornos-pagination', [RetornoController::class, 'paginatedIndex']);
    Route::get('/facturas-pagination', [FacturaController::class, 'paginatedIndex']);
    Route::get('/metodo-pagination', [MetodoPagoController::class, 'paginatedIndex']);

    //rutas de confirmar pagos
    Route::post('/confirmar-pago/{id}', [UsuarioController::class, 'confirmarPago'])->name('pago.confirmar');
    Route::post('/rechazar-pago/{id}', [UsuarioController::class, 'rechazarPago'])->name('pago.rechazar');


    //rutas de planes
    
    Route::post('/planes', [PlanController::class, 'store']);
    Route::get('/planes/{id}', [PlanController::class, 'show']);
    Route::put('/planes/{id}', [PlanController::class, 'update']);

    //ruta de promociones
    Route::get('/promociones', [PromocionController::class, 'index']);
    Route::get('/promociones/{id}', [PromocionController::class, 'show']);
    Route::post('/promociones', [PromocionController::class, 'store']);
    Route::put('/promociones/{id}', [PromocionController::class, 'update']);
    Route::delete('/promociones/{id}', [PromocionController::class, 'destroy']);

    //rutas de metodos de pago
    Route::get('/metodos-pago', [MetodoPagoController::class, 'index']);
    Route::get('/metodos-pago/{id}', [MetodoPagoController::class, 'show']);
    Route::post('/metodos-pago', [MetodoPagoController::class, 'store']);
    Route::put('/metodos-pago/{id}', [MetodoPagoController::class, 'update']);
    Route::delete('/metodos-pago/{id}', [MetodoPagoController::class, 'destroy']);
});
