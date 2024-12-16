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
use App\Http\Controllers\ExportarController;




//Rutas principales
Route::post('/planes', [PlanController::class, 'store']);
Route::post('/confirmar-pago/{id}', [UsuarioController::class, 'confirmarPago'])->name('pago.confirmar');
Route::post('/metodos-pago', [MetodoPagoController::class, 'store']);


//rutas de paypal publicas
Route::get('/payment/success', [UsuarioController::class, 'paymentSuccess'])->name('paypal.payment.success');
Route::get('/payment/cancel', [UsuarioController::class, 'paymentCancel'])->name('paypal.payment.cancel');
Route::get('/payment/failure', [UsuarioController::class, 'paymentFailure'])->name('paypal.payment.failure');

//Ruta para ver los planes
Route::get('/planes', [PlanController::class, 'index']);

//rutas chatbot
Route::post('/chat', [ChatBotController::class, 'chat']); // Ruta para el chat
Route::post('/question', [ChatBotController::class, 'store']); // Ruta para almacenar preguntas y respuestas
Route::get('/questions', [ChatBotController::class, 'getAllQuestions']); // Ruta para obtener todas las preguntas con paginación


//rutas de autenticación
Route::post('/login', [LoginController::class, 'login']);  // Ruta para el login
Route::post('/register', [UsuarioController::class, 'register']);  // Ruta para el registro de usuarios
Route::post('/register-admins', [UsuarioController::class, 'registerAdmins']);
//recuperar contra
Route::post('/forget', [UsuarioController::class, 'recoveryPassword']);
Route::post('/reset-password', [UsuarioController::class, 'resetPassword']);

//requets demo
Route::post('/demo', [UsuarioController::class, 'requestDemo']);

Route::get('/usuarios/all', [UsuarioController::class, 'indexAdmins']);



//Agrupar por middleware perzonalizado
Route::middleware('TenantAuthPermissions')->group(function () { 

    //Rutas de exportacion de datos
    Route::get('exportar/excel', [ExportarController::class, 'exportarExcel']);
    Route::get('exportar/sql', [ExportarController::class, 'exportarSQL']);


    //Ruta de registro empleado
    Route::post('/register/employee', [UsuarioController::class, 'registerForEmployee']);

    //rutas de usuarios
    Route::get('/usuarios', [UsuarioController::class, 'index']);  // Ruta para listar usuarios
    Route::get('/usuarios/{id}', [UsuarioController::class, 'show']);  // Ruta para mostrar un usuario específico
    Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy']);  // Ruta para eliminar un usuario
    Route::put('/usuario/{id}', [UsuarioController::class, 'update']);  // Ruta para actualizar un usuario

    //rutas de roles
    Route::get('/roles', [RolController::class, 'index']);  // Ruta para listar roles
    Route::post('/roles', [RolController::class, 'store']);  // Ruta para crear un rol
    Route::get('/roles/{id}', [RolController::class, 'show']);  // Ruta para mostrar un rol específico
    Route::put('/roles/{id}', [RolController::class, 'update']);  // Ruta para actualizar un rol
    Route::delete('/roles/{id}', [RolController::class, 'destroy']);  // Ruta para eliminar un rol

    //rutas de permisos
    Route::get('/permisos', [PermisoController::class, 'index']);  // Ruta para listar permisos
    Route::get('/permisos/{id}', [PermisoController::class, 'show']);  // Ruta para mostrar un permiso específico

    // Listar roles y sus permisos
    Route::get('roles', [RolPermisoController::class, 'index']); // Listar roles y sus permisos
    Route::post('roles/permiso', [RolPermisoController::class, 'store']); // Asignar un permiso a un rol
    Route::get('roles/{rolId}/permisos', [RolPermisoController::class, 'show']); // Obtener permisos de un rol
    Route::delete('roles/permiso/{permisoId}', [RolPermisoController::class, 'destroy']); // Eliminar un permiso de un rol

    //rutas de sitios
    Route::post('/sitios', [SitioController::class, 'store']);
    Route::get('/sitios', [SitioController::class, 'index']);
    Route::get('/sitios/{id}', [SitioController::class, 'show']);
    Route::put('/sitios/{id}', [SitioController::class, 'update']);
    Route::delete('/sitios/{id}', [SitioController::class, 'destroy']);

    //rutas de etiquetas
    Route::get('/etiquetas', [EtiquetaController::class, 'index']);
    Route::get('/etiquetas/{id}', [EtiquetaController::class, 'show']);
    Route::post('/etiquetas', [EtiquetaController::class, 'store']);
    Route::put('/etiquetas/{id}', [EtiquetaController::class, 'update']);
    Route::delete('/etiquetas/{id}', [EtiquetaController::class, 'destroy']);

    //rutas de proveedores
    Route::get('/proveedores', [ProveedorController::class, 'index']);
    Route::get('/proveedores/{id}', [ProveedorController::class, 'show']);
    Route::put('/proveedores/{id}', [ProveedorController::class, 'update']);
    Route::delete('/proveedores/{id}', [ProveedorController::class, 'destroy']);
    Route::post('/proveedor', [ProveedorController::class, 'store']);


    //rutas de productos
    Route::get('/productos/{id}', [ProductoController::class, 'show']);
    Route::get('/productos', [ProductoController::class, 'index']);
    Route::post('/productos', [ProductoController::class, 'store']);
    Route::delete('/productos/{id}', [ProductoController::class, 'destroy']);
    Route::put('/productos/{id}', [ProductoController::class, 'update']);

    //rutas barcodes
    Route::get('producto/{id}/barcode', [ProductoController::class, 'verCodigoDeBarras']);
    Route::get('lote/{id}/barcode', [LoteController::class, 'verCodigoDeBarras']);
    Route::post('/BC-Lote', [LoteController::class, 'showByCodigoLote']);

    //rutas de lotes
    Route::get('/lotes', [LoteController::class, 'index']);
    Route::get('/lotes/{id}', [LoteController::class, 'show']);
    Route::post('/lotes', [LoteController::class, 'store']);
    Route::put('/lotes/{id}', [LoteController::class, 'update']);
    Route::delete('/lotes/{id}', [LoteController::class, 'destroy']);


    //rutas de comprobantes
    Route::get('/comprobantes', [ComprobanteController::class, 'index']);  // Listar todos los comprobantes
    Route::get('/comprobantes/{id}', [ComprobanteController::class, 'show']);  // Mostrar un comprobante específico
    Route::post('/comprobantes', [ComprobanteController::class, 'store']);
    Route::get('/comprobante/{id}/pdf', [ComprobanteController::class, 'generarPDF']);
    Route::get('/comprobantes/sitio/{id}', [ComprobanteController::class, 'obtenerComprobantesPorSitio']);

    //rutas de retorno
    Route::get('/retornos', [RetornoController::class, 'index']);
    Route::get('/retornos/{id}', [RetornoController::class, 'show']);
    Route::post('/retornos', [RetornoController::class, 'store']);;

    //Rutas para Rol a Usuarios (UsuarioRolController)
    Route::post('/asignar/rol', [UsuarioRolController::class, 'store']);



    //Ruta para ver las facturas
    Route::get('/facturas', [FacturaController::class, 'index']);
    Route::get('/facturas/{id}', [FacturaController::class, 'show']);
});



//rutas protegidas (Bd Master)
Route::middleware('auth:api')->group(function () {
    


    //rutas de demo
    Route::post('/demo/approve/', [UsuarioController::class, 'approveDemo']);
    Route::post('/demo/reject/', [UsuarioController::class, 'rejectDemo']);

    //rutas de manejo de pagos bancarios
    Route::post('/rechazar-pago/{id}', [UsuarioController::class, 'rechazarPago'])->name('pago.rechazar');
    Route::put('/usuarios/habilitar/{id}', [UsuarioController::class, 'habilitarUsuario'])->name('api.usuarios.habilitar');

    //rutas de planes
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
    Route::put('/metodos-pago/{id}', [MetodoPagoController::class, 'update']);
    Route::delete('/metodos-pago/{id}', [MetodoPagoController::class, 'destroy']);

    //Funcion de admin
    
    
});
