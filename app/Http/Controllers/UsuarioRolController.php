<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class UsuarioRolController extends Controller
{
    /**
     * Verifica si el usuario autenticado tiene un rol específico.
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

    // Asignar un rol a un usuario
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
            // Los administradores pueden asignar roles directamente en la base de datos principal
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
            'usuario_id' => 'required|exists:usuarios,id',
            'rol_id' => 'required|exists:roles,id',
        ]);

        $usuario = Usuario::findOrFail($request->usuario_id);
        $rol = Rol::findOrFail($request->rol_id);

        $usuario->roles()->attach($rol);
        return response()->json(['message' => 'Rol asignado al usuario correctamente']);
    }

    // Remover un rol de un usuario
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
            // Los administradores pueden remover roles directamente en la base de datos principal
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
            'usuario_id' => 'required|exists:usuarios,id',
            'rol_id' => 'required|exists:roles,id',
        ]);

        $usuario = Usuario::findOrFail($request->usuario_id);
        $rol = Rol::findOrFail($request->rol_id);

        $usuario->roles()->detach($rol);
        return response()->json(['message' => 'Rol removido del usuario correctamente']);
    }

    // Obtener los roles de un usuario
    public function show($usuarioId)
    {
        $usuario = Usuario::findOrFail($usuarioId);
        $roles = $usuario->roles;
        return response()->json($roles);
    }
}
