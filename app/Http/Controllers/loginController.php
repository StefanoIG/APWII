<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

class loginController extends Controller
{
    public function login(Request $request)
    {
        // Validamos los datos que llegan en el request
        $credentials = $request->only('correo_electronico', 'password');

        // Registramos en el log los datos recibidos para depuración
        Log::info('Intento de login con las siguientes credenciales:', $credentials);

        try {
            // Intentamos autenticar al usuario
            if (!$token = Auth::attempt(['correo_electronico' => $credentials['correo_electronico'], 'password' => $credentials['password']])) {
                // Si falla la autenticación, devolvemos un error 401
                Log::warning('Credenciales inválidas para el correo: ' . $request->correo_electronico);
                return response()->json(['error' => 'Credenciales inválidas'], 401);
            }

            // Autenticación exitosa, generamos el token JWT
            $user = Auth::user();
            Log::info('Autenticación exitosa para el usuario: ' . $user->correo_electronico);

            // Devolvemos el token en la respuesta
            return response()->json(compact('token'));
        } catch (\Exception $e) {
            // En caso de error, registramos el error en el log y devolvemos un error 500
            Log::error('Error durante el proceso de login: ' . $e->getMessage());
            return response()->json(['error' => 'Error en el servidor'], 500);
        }
    }
}