<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\loginController;

Route::post('/login', [loginController::class, 'login']); // Ruta para el login
 Route::post('/register', [loginController::class, 'register']); // Ruta para el registro