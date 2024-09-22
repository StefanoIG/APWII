<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cookie; // Para manipular cookies

class loginController extends Controller
{
    public function login(Request $request)
    {
        // Validamos los datos que llegan en el request
        $credentials = $request->only('correo_electronico', 'password');

        Log::info('Intento de login con las siguientes credenciales:', $credentials);

        try {
            if (!$token = Auth::attempt(['correo_electronico' => $credentials['correo_electronico'], 'password' => $credentials['password']])) {
                Log::warning('Credenciales inválidas para el correo: ' . $request->correo_electronico);
                return response()->json(['error' => 'Credenciales inválidas'], 401);
            }

            $user = Auth::user();

            if ($user->deleted_at) {
                Log::warning('Intento de login para una cuenta eliminada: ' . $user->correo_electronico);
                return response()->json(['error' => 'Cuenta expirada'], 403);
            }

            Log::info('Autenticación exitosa para el usuario: ' . $user->correo_electronico);

            // Configuración de la cookie con opciones SameSite y Secure
            $secure = ('APP_ENV') === 'production'; // Solo marcar como 'Secure' en producción

            $tokenCookie = cookie(
                'token', // Nombre de la cookie
                $token, // Valor de la cookie (en este caso, el token JWT)
                60, // Duración de 60 minutos (1 hora)
                '/', // Ruta donde la cookie es válida
                null, // Dominio (null para usar el dominio actual)
                $secure, // Solo usar "secure" en producción
                false, // HttpOnly en false para que sea accesible desde JavaScript
                false, // Sin formato crudo
                'Lax' // SameSite
            );

            $roleCookie = cookie(
                'role', // Nombre de la cookie
                $user->rol, // Valor de la cookie (el rol del usuario)
                60, // Duración de 60 minutos (1 hora)
                '/', // Ruta donde la cookie es válida
                null, // Dominio (null para usar el dominio actual)
                $secure, // Solo usar "secure" en producción
                false, // HttpOnly en false para que sea accesible desde JavaScript
                false, // Sin formato crudo
                'Lax' // SameSite
            );

            $responseData = [
                'message' => 'Autenticación exitosa',
                'token' => $token,
                'role' => $user->rol
            ];

            return response()->json($responseData)
                ->cookie($tokenCookie)
                ->cookie($roleCookie);
        } catch (\Exception $e) {
            Log::error('Error durante el proceso de login: ' . $e->getMessage());
            return response()->json(['error' => 'Error en el servidor'], 500);
        }
    }
}