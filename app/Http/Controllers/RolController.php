<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RolController extends Controller
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

    // Obtener todos los roles
    public function index()
    {
        $roles = Rol::all();
        return response()->json($roles);
    }

    // Crear un nuevo rol
    public function store(Request $request)
    {
        if ($this->verificarRol('Admin')) {
            // Admin puede crear roles directamente en la base de datos principal
            $request->validate([
                'nombre' => 'required|unique:roles',
                'descripcion' => 'nullable|string',
            ]);

            $rol = Rol::create($request->all());
            return response()->json($rol, 201);
        } elseif ($this->verificarRol('Owner')) {
            // Validar el nombre de la base de datos del tenant
            $validator = Validator::make($request->all(), [
                'tenant_database' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Establecer la conexión al tenant
            $tenantDatabase = $request->tenant_database;
            $this->setTenantConnection($tenantDatabase);

            // Verificar que la conexión se haya establecido
            if (!DB::connection('tenant')->getDatabaseName()) {
                return response()->json(['error' => 'No se pudo conectar a la base de datos del tenant'], 500);
            }

            // Crear el rol en la base de datos del tenant
            $request->validate([
                'nombre' => 'required|unique:roles',
                'descripcion' => 'nullable|string',
            ]);

            $rol = Rol::create($request->all());
            return response()->json($rol, 201);
        } else {
            return response()->json(['error' => 'No tienes permisos para crear roles'], 403);
        }
    }

    // Mostrar un rol específico
    public function show($id)
    {
        $rol = Rol::findOrFail($id);
        return response()->json($rol);
    }

    // Actualizar un rol
    public function update(Request $request, $id)
    {
        $rol = Rol::findOrFail($id);

        if ($this->verificarRol('Admin')) {
            // Admin puede actualizar roles directamente en la base de datos principal
            $request->validate([
                'nombre' => 'required|unique:roles,nombre,' . $rol->id,
                'descripcion' => 'nullable|string',
            ]);

            $rol->update($request->all());
            return response()->json($rol);
        } elseif ($this->verificarRol('Owner')) {
            // Validar el nombre de la base de datos del tenant
            $validator = Validator::make($request->all(), [
                'tenant_database' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Establecer la conexión al tenant
            $tenantDatabase = $request->tenant_database;
            $this->setTenantConnection($tenantDatabase);

            // Verificar que la conexión se haya establecido
            if (!DB::connection('tenant')->getDatabaseName()) {
                return response()->json(['error' => 'No se pudo conectar a la base de datos del tenant'], 500);
            }

            // Actualizar el rol en la base de datos del tenant
            $request->validate([
                'nombre' => 'required|unique:roles,nombre,' . $rol->id,
                'descripcion' => 'nullable|string',
            ]);

            $rol->update($request->all());
            return response()->json($rol);
        } else {
            return response()->json(['error' => 'No tienes permisos para actualizar roles'], 403);
        }
    }

    // Eliminar un rol
    public function destroy($id)
    {
        $rol = Rol::findOrFail($id);

        if ($this->verificarRol('Admin')) {
            // Admin puede eliminar roles directamente en la base de datos principal
            $rol->delete();
            return response()->json(null, 204);
        } elseif ($this->verificarRol('Owner')) {
            // Validar el nombre de la base de datos del tenant
            $validator = Validator::make(request()->all(), [
                'tenant_database' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Establecer la conexión al tenant
            $tenantDatabase = request()->tenant_database;
            $this->setTenantConnection($tenantDatabase);

            // Verificar que la conexión se haya establecido
            if (!DB::connection('tenant')->getDatabaseName()) {
                return response()->json(['error' => 'No se pudo conectar a la base de datos del tenant'], 500);
            }

            // Eliminar el rol en la base de datos del tenant
            $rol->delete();
            return response()->json(null, 204);
        } else {
            return response()->json(['error' => 'No tienes permisos para eliminar roles'], 403);
        }
    }
}
