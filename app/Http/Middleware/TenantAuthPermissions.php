<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash; // Asegúrate de tener el uso de Hash para verificar contraseñas

class TenantAuthPermissions
{
    public function handle(Request $request, Closure $next, $requiredPermissions = null)
    {
        // Obtener el usuario autenticado
        $user = Auth::user();

        // Obtener el nombre del archivo SQLite desde el header o el parámetro de consulta
        $tenantDatabase = $request->header('X-Tenant') ?? $request->query('tenant');

        Log::info('Obteniendo el nombre del archivo SQLite', ['tenantDatabase' => $tenantDatabase]);

        if (!$tenantDatabase) {
            Log::error('Tenant no especificado en la solicitud');
            return response()->json(['error' => 'Tenant no especificado'], 400);
        }

        // Verificar si el archivo SQLite existe en la carpeta "database/tenants/"
        $tenantPath = database_path('tenants/' . $tenantDatabase);

        if (!file_exists($tenantPath)) {
            Log::error('Archivo SQLite del tenant no encontrado', ['tenantPath' => $tenantPath]);
            return response()->json(['error' => 'Base de datos del tenant no encontrada '], 404);
        }

        // Verificar si el archivo SQLite existe y se puede conectar
        $this->setTenantConnection($tenantPath);

        // Verificar que la conexión al tenant está activa
        if (!DB::connection('tenant')->getDatabaseName()) {
            Log::error('No se pudo conectar a la base de datos del tenant', ['tenantPath' => $tenantPath]);
            return response()->json(['error' => 'No se pudo conectar a la base de datos del tenant '], 500);
        }

        // Buscar al usuario en la tabla "usuarios" del tenant para verificar correo y contraseña
        $tenantUser = DB::connection('tenant')->table('usuarios')
            ->where('correo_electronico', $user->correo_electronico)
            ->first();

        if (!$tenantUser) {
            Log::error('Usuario no encontrado en el tenant', ['correo_electronico' => $user->correo_electronico, 'tenantPath' => $tenantPath]);
            return response()->json(['error' => 'Usuario no encontrado en el tenant'], 404);
        }

     
        Log::info('Usuario autenticado correctamente en el tenant', ['correo_electronico' => $user->correo_electronico, 'tenantPath' => $tenantPath]);

        // Verificación de permisos si se proporcionan
        if ($requiredPermissions) {
            $permissions = explode(',', $requiredPermissions);
            $hasPermission = $user->roles->some(
                fn($rol) =>
                $rol->permisos()->whereIn('nombre', $permissions)->exists()
            );

            if (!$hasPermission) {
                Log::error('Permiso insuficiente', ['user' => $user, 'permissions' => $permissions]);
                return response()->json(['error' => 'Permiso insuficiente'], 403);
            }
        }

        return $next($request);
    }

    // Método para establecer la conexión al tenant
    protected function setTenantConnection($tenantPath)
    {
        // Configurar la conexión con la base de datos SQLite del tenant
        config(['database.connections.tenant' => [
            'driver' => 'sqlite',
            'database' => $tenantPath, // Usar el path completo para conectar
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);

        DB::purge('tenant');
        DB::reconnect('tenant');

        Log::info('Conexión al tenant establecida', ['tenantPath' => $tenantPath]);
    }
}
