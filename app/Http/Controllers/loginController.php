<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

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

                // Cargar roles del usuario desde la tabla intermedia
                $user->load('roles'); // Asumiendo que la relación está definida como 'roles'

                // Verificar si el usuario tiene el rol de 'Admin'
                if ($user->roles->contains('nombre', 'Admin')) {
                    return $this->sendSuccessResponse($token, $user, 'master');
                }
            }

            // Si no tiene rol de 'Admin', buscar en las bases de datos de tenants
            Log::info('No se encontró rol de Admin en la base de datos principal. Buscando en tenants...');

            // Obtener todas las bases de datos de tenants
            $tenantDatabases = File::files(database_path('tenants'));

            foreach ($tenantDatabases as $tenantDatabase) {
                // Extraer el nombre de la base de datos
                $databasePath = $tenantDatabase->getPathname();

                try {
                    // Configurar la conexión para el tenant
                    config(['database.connections.tenant' => [
                        'driver' => 'sqlite',
                        'database' => $databasePath,
                        'prefix' => '',
                        'foreign_key_constraints' => true,
                    ]]);

                    // Establecer la conexión del tenant
                    DB::purge('tenant');
                    DB::reconnect('tenant');

                    Log::info('Conexión establecida a la base de datos del tenant: ' . $databasePath);

                    // Intentar autenticar al usuario en la base de datos del tenant
                    if ($token = Auth::attempt(['correo_electronico' => $credentials['correo_electronico'], 'password' => $credentials['password']])) {
                        // Obtener el usuario autenticado desde el tenant
                        $user = Auth::user();

                        // Cargar roles del usuario desde la tabla intermedia
                        $user->load('roles');

                        // Comprobar si el usuario fue eliminado (soft delete)
                        if ($user->deleted_at) {
                            Log::warning('Intento de login para una cuenta eliminada: ' . $user->correo_electronico);
                            return response()->json(['error' => 'Cuenta expirada'], 403);
                        }

                        Log::info('Autenticación exitosa en tenant: ' . $databasePath . ' para el usuario: ' . $user->correo_electronico);

                        // Devolver respuesta exitosa con el token, rol y el nombre de la base de datos
                        return $this->sendSuccessResponse($token, $user, basename($databasePath));
                    } else {
                        Log::info('Usuario no encontrado en tenant: ' . $databasePath);
                    }
                } catch (\Exception $e) {
                    Log::error('Error al conectar con el tenant: ' . $databasePath . '. Error: ' . $e->getMessage());
                }
            }

            Log::warning('Usuario no encontrado en ningún tenant con el correo: ' . $credentials['correo_electronico']);
            return response()->json(['error' => 'Usuario no encontrado en ningún tenant'], 404);
        } catch (\Exception $e) {
            Log::error('Error durante el proceso de login: ' . $e->getMessage());
            return response()->json(['error' => 'Error en el servidor'], 500);
        }
    }

    /**
     * Enviar respuesta de éxito con cookies y token.
     *
     * @param string $token
     * @param \App\Models\User $user
     * @param string $databaseName El nombre de la base de datos (puede ser 'master' o el nombre del archivo SQLite)
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendSuccessResponse($token, $user, $databaseName)
    {
        $secure = ('APP_ENV') === 'production'; // Solo marcar como 'Secure' en producción

        // Extraer el primer rol del usuario (asumiendo que el usuario solo tiene un rol en este caso)
        $role = $user->roles->first()->nombre ?? 'Sin rol';  // Verificamos si tiene al menos un rol

        // Crear cookies para el token, rol y la base de datos del usuario
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
            $role, // Aquí obtenemos el rol del usuario
            60, // Duración de 60 minutos (1 hora)
            '/', // Ruta válida en todas las rutas
            null, // Dominio (null para usar el dominio actual o agregar el tuyo para subdominios)
            $secure, // Solo usar "secure" en producción
            false, // HttpOnly en false para que sea accesible desde JavaScript
            false, // Sin formato crudo
            'Lax' // SameSite
        );

        $tenantDatabaseCookie = cookie(
            'tenant_database',
            $databaseName, // Nombre de la base de datos del tenant (o 'master')
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
            'role' => $role,  // Enviar el rol del usuario en la respuesta
            'tenant_database' => $databaseName // Enviar la base de datos del tenant en la respuesta
        ])->cookie($tokenCookie)->cookie($roleCookie)->cookie($tenantDatabaseCookie);
    }
}
