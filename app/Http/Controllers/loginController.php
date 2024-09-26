<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant;
use Illuminate\Support\Facades\Config;

class loginController extends Controller
{
    public function login(Request $request)
    {
        // Validamos los datos que llegan en el request
        $credentials = $request->only('correo_electronico', 'password');

        Log::info('Intento de login con las siguientes credenciales:', $credentials);

        try {
            // Intentar autenticarse en la base de datos principal
            if ($token = Auth::attempt(['correo_electronico' => $credentials['correo_electronico'], 'password' => $credentials['password']])) {
                $user = Auth::user();

                // Si es admin o pertenece a la base de datos principal, devolvemos el token
                if ($user->rol === 'Admin') {
                    return $this->sendSuccessResponse($token, $user);
                }
            }

            // Si no es admin, buscar en la base de datos principal si el usuario está asociado a algún tenant
            $tenant = Tenant::whereHas('usuarios', function ($query) use ($credentials) {
                $query->where('correo_electronico', $credentials['correo_electronico']);
            })->first();

            if (!$tenant) {
                Log::warning('Usuario no asociado a ningún tenant con el correo: ' . $credentials['correo_electronico']);
                return response()->json(['error' => 'Usuario no encontrado en ningún tenant'], 404);
            }

            // Cambiar la conexión a la base de datos del tenant
            $this->switchToTenantDatabase($tenant);

            // Intentar autenticar al usuario en la base de datos del tenant
            if (!$token = Auth::attempt(['correo_electronico' => $credentials['correo_electronico'], 'password' => $credentials['password']])) {
                Log::warning('Credenciales inválidas para el correo: ' . $credentials['correo_electronico']);
                return response()->json(['error' => 'Credenciales inválidas'], 401);
            }

            // Obtener el usuario autenticado desde el tenant
            $user = Auth::user();

            // Comprobar si el usuario fue eliminado (soft delete)
            if ($user->deleted_at) {
                Log::warning('Intento de login para una cuenta eliminada: ' . $user->correo_electronico);
                return response()->json(['error' => 'Cuenta expirada'], 403);
            }

            Log::info('Autenticación exitosa para el usuario: ' . $user->correo_electronico);

            // Devolver respuesta exitosa con el token y rol del usuario
            return $this->sendSuccessResponse($token, $user);

        } catch (\Exception $e) {
            Log::error('Error durante el proceso de login: ' . $e->getMessage());
            return response()->json(['error' => 'Error en el servidor'], 500);
        }
    }

    /**
     * Cambiar dinámicamente la conexión a la base de datos del tenant.
     *
     * @param Tenant $tenant
     * @return void
     */
    protected function switchToTenantDatabase(Tenant $tenant)
    {
        // Configurar la conexión a la base de datos del tenant
        Config::set('database.connections.tenant', [
            'driver' => 'sqlite', // O el driver que uses (mysql, etc.)
            'database' => database_path('tenants/' . $tenant->domain . '.sqlite'), // Aquí defines la base de datos del tenant
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        // Reconectar a la base de datos del tenant
        DB::purge('tenant');
        DB::reconnect('tenant');
        Log::info('Conexión cambiada a la base de datos del tenant: ' . $tenant->name);
    }

    /**
     * Enviar respuesta de éxito con cookies y token.
     *
     * @param string $token
     * @param \App\Models\User $user
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendSuccessResponse($token, $user)
    {
        $secure = ('APP_ENV') === 'production'; // Solo marcar como 'Secure' en producción

        // Crear cookies para el token y rol del usuario
        $tokenCookie = cookie(
            'token',
            $token,
            60, // Duración de 60 minutos (1 hora)
            '/', // Ruta válida en todas las rutas
            null, // Dominio (null para usar el dominio actual o agregar el tuyo para subdominios)
            $secure, // Solo usar "secure" en producción
            false, // HttpOnly en false para que sea accesible desde JavaScript
            false, // Sin formato crudo
            'Lax' // SameSite
        );

        $roleCookie = cookie(
            'role',
            $user->rol,
            60, // Duración de 60 minutos (1 hora)
            '/', // Ruta válida en todas las rutas
            null, // Dominio (null para usar el dominio actual o agregar el tuyo para subdominios)
            $secure, // Solo usar "secure" en producción
            false, // HttpOnly en false para que sea accesible desde JavaScript
            false, // Sin formato crudo
            'Lax' // SameSite
        );

        // Devolver respuesta exitosa
        return response()->json([
            'message' => 'Autenticación exitosa',
            'token' => $token,
            'role' => $user->rol
        ])->cookie($tokenCookie)->cookie($roleCookie);
    }
}
