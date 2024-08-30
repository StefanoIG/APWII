<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UsuarioController;

Route::post('/login', [LoginController::class, 'login']);  // Ruta para el login
Route::post('/register', [UsuarioController::class, 'register']);  // Ruta para el registro de usuarios
Route::get('/usuarios', [UsuarioController::class, 'index']);  // Ruta para listar usuarios
Route::put('/usuarios/{id}', [UsuarioController::class, 'update']);  // Ruta para actualizar usuario
