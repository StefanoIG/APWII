<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        // Validar las credenciales recibidas
        $credentials = $request->only('correo_electronico', 'password');

        Log::info('Intento de login con las siguientes credenciales:', $credentials);

        try {
            // Intentar autenticar en la base de datos principal
            if ($token = Auth::attempt(['correo_electronico' => $credentials['correo_electronico'], 'password' => $credentials['password']])) {
                $user = Auth::user();

                // Cargar roles del usuario
                $user->load('roles');

                // Si el usuario tiene el rol de 'Admin', devuelve éxito con la base de datos principal
                if ($user->roles->contains('nombre', 'Admin')) {
                    return $this->sendSuccessResponse($token, $user, 'master');
                }
            }

            // Buscar el tenant al que pertenece el usuario en la base de datos principal
            $tenant = Tenant::whereHas('usuarios', function ($query) use ($credentials) {
                $query->where('correo_electronico', $credentials['correo_electronico']);
            })->first();

            if (!$tenant) {
                Log::warning('Usuario no encontrado en ningún tenant con el correo: ' . $credentials['correo_electronico']);
                return response()->json(['error' => 'Usuario no encontrado en ningún tenant'], 404);
            }

            // Verificar y acceder directamente al campo `database_path`
            if (empty($tenant->database_path)) {
                Log::error('El campo `database_path` está vacío para el tenant: ' . $tenant->name);
                return response()->json(['error' => 'No se pudo encontrar la base de datos del tenant.'], 500);
            }

            // Normalizar el `database_path` para garantizar consistencia
            $databasePath = str_replace(['\\', '//'], '/', $tenant->database_path);
            $databasePath = basename($databasePath); // Extraer solo el nombre del archivo

            // Verificar si la ruta contiene un archivo válido
            if (empty($databasePath)) {
                Log::error("El campo `database_path` no contiene un nombre de archivo válido para el tenant: {$tenant->name}");
                return response()->json(['error' => 'Error al procesar la información del tenant'], 500);
            }

            Log::info("Base de datos del tenant encontrada: $databasePath");

            // Configurar la conexión del tenant usando el `database_path`
            config(['database.connections.tenant' => [
                'driver' => 'sqlite',
                'database' => $tenant->database_path, // Usar el path completo para conectar
                'prefix' => '',
                'foreign_key_constraints' => true,
            ]]);

            DB::purge('tenant');
            DB::reconnect('tenant');

            // Cambiar dinámicamente el provider de autenticación para usar la conexión del tenant
            $usuarioModel = new \App\Models\Usuario;
            $usuarioModel->setConnection('tenant'); // Cambiar la conexión del modelo

            Auth::setProvider(
                new \Illuminate\Auth\EloquentUserProvider(
                    app('hash'),
                    get_class($usuarioModel) // Obtener el nombre de la clase correctamente
                )
            );

            // Intentar autenticar en la base de datos del tenant
            if ($token = Auth::attempt(['correo_electronico' => $credentials['correo_electronico'], 'password' => $credentials['password']])) {
                $user = Auth::user();

                // Cargar roles del usuario
                $user->load('roles');

                // Comprobar si el usuario está eliminado (soft delete)
                if ($user->deleted_at) {
                    Log::warning('Intento de login para una cuenta eliminada: ' . $user->correo_electronico);
                    return response()->json(['error' => 'Cuenta expirada'], 403);
                }

                Log::info('Autenticación exitosa en tenant: ' . $tenant->name . ' para el usuario: ' . $user->correo_electronico);

                return $this->sendSuccessResponse($token, $user, $databasePath);
            }

            Log::info('Credenciales inválidas para el tenant: ' . $tenant->name);
            return response()->json(['error' => 'Credenciales inválidas'], 401);
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
        $secure = env('APP_ENV') === 'production';

        // Extraer el primer rol del usuario
        $role = $user->roles->first()->nombre ?? 'Sin rol';

        return response()->json([
            'message' => 'Autenticación exitosa',
            'token' => $token,
            'role' => $role,
            'tenant_database' => $databaseName,
        ]);
    }
}