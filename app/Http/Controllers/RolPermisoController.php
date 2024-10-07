<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Permiso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RolPermisoController extends Controller
{
    /**
     * Verifica si el usuario autenticado tiene un permiso específico.
     */
    private function verificarPermiso($permisoNombre)
    {
        try {
            $user = Auth::user();
            $roles = $user->roles;

            foreach ($roles as $rol) {
                if ($rol->permisos()->where('nombre', $permisoNombre)->exists()) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Error en verificarPermiso: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica si el usuario autenticado tiene un rol específico.
     *
     * @param string $rolNombre
     * @return bool
     */
    private function verificarRol($rolNombre)
    {
        try {
            $user = Auth::user();
            $roles = $user->roles;

            foreach ($roles as $rol) {
                if ($rol->nombre === $rolNombre) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Error en verificarRol: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Establecer la conexión al tenant correspondiente usando el nombre de la base de datos.
     */
    protected function setTenantConnection($databaseName)
    {
        // Configurar la conexión a la base de datos del tenant
        config(['database.connections.tenant' => [
            'driver' => 'sqlite',
            'database' => database_path('tenants/' . $databaseName . '.sqlite'),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);

        // Purga la conexión anterior y reconecta con el tenant
        DB::purge('tenant');
        DB::reconnect('tenant');

        // Establecer el nombre de la conexión de forma predeterminada
        DB::setDefaultConnection('tenant');
    }

    // Asignar un permiso a un rol
    public function store(Request $request)
    {
        // Validar el nombre de la base de datos del tenant
        $validator = Validator::make($request->all(), [
            'tenant_database' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verificar el rol del usuario autenticado
        if ($this->verificarRol('Admin')) {
            // Los administradores pueden asignar permisos directamente en la base de datos principal
        } elseif ($this->verificarRol('Owner')) {
            // Establecer la conexión al tenant si es 'Owner'
            $tenantDatabase = $request->tenant_database;
            $this->setTenantConnection($tenantDatabase);

            // Verificar que la conexión se haya establecido
            if (!DB::connection('tenant')->getDatabaseName()) {
                return response()->json(['error' => 'No se pudo conectar a la base de datos del tenant'], 500);
            }
        } else {
            return response()->json(['error' => 'No tienes permiso para realizar esta acción'], 403);
        }

        $request->validate([
            'rol_id' => 'required|exists:roles,id',
            'permiso_id' => 'required|exists:permisos,id',
        ]);

        $rol = Rol::findOrFail($request->rol_id);
        $permiso = Permiso::findOrFail($request->permiso_id);

        $rol->permisos()->attach($permiso);
        return response()->json(['message' => 'Permiso asignado al rol correctamente']);
    }

    // Remover un permiso de un rol
    public function destroy(Request $request)
    {
        // Validar el nombre de la base de datos del tenant
        $validator = Validator::make($request->all(), [
            'tenant_database' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Verificar el rol del usuario autenticado
        if ($this->verificarRol('Admin')) {
            // Los administradores pueden eliminar permisos directamente en la base de datos principal
        } elseif ($this->verificarRol('Owner')) {
            // Establecer la conexión al tenant si es 'Owner'
            $tenantDatabase = $request->tenant_database;
            $this->setTenantConnection($tenantDatabase);

            // Verificar que la conexión se haya establecido
            if (!DB::connection('tenant')->getDatabaseName()) {
                return response()->json(['error' => 'No se pudo conectar a la base de datos del tenant'], 500);
            }
        } else {
            return response()->json(['error' => 'No tienes permiso para realizar esta acción'], 403);
        }

        $request->validate([
            'rol_id' => 'required|exists:roles,id',
            'permiso_id' => 'required|exists:permisos,id',
        ]);

        $rol = Rol::findOrFail($request->rol_id);
        $permiso = Permiso::findOrFail($request->permiso_id);

        $rol->permisos()->detach($permiso);
        return response()->json(['message' => 'Permiso removido del rol correctamente']);
    }

    // Obtener los permisos de un rol
    public function show($rolId)
    {
        $rol = Rol::findOrFail($rolId);
        $permisos = $rol->permisos;
        return response()->json($permisos);
    }
}
