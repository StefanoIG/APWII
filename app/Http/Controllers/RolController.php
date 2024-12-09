<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class RolController extends Controller
{
    /**
     * Establecer la conexión al tenant correspondiente usando el nombre de la base de datos.
     */
    protected function setTenantConnection(Request $request)
    {
        $tenantDatabase = $request->header('X-Tenant');

        if (!$tenantDatabase) {
            abort(400, 'El encabezado X-Tenant es obligatorio.');
        }

        config(['database.connections.tenant' => [
            'driver' => 'sqlite',
            'database' => database_path('tenants/' . $tenantDatabase),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);

        DB::purge('tenant');
        DB::reconnect('tenant');
        DB::setDefaultConnection('tenant');
    }

    /**
     * Verificar si el usuario autenticado tiene un permiso específico.
     */
    private function verificarPermiso($permisoNombre)
    {
        $user = Auth::user();

        if (!$user) {
            Log::error('Usuario no autenticado al verificar permiso: ' . $permisoNombre);
            return false;
        }

        $user->load('roles.permisos');

        foreach ($user->roles as $rol) {
            if ($rol->permisos->contains('nombre', $permisoNombre)) {
                return true;
            }
        }

        Log::warning('Permiso no encontrado: ' . $permisoNombre . ' para el usuario: ' . $user->id);
        return false;
    }

    /**
     * Verificar si el usuario autenticado tiene un rol específico.
     */
    private function verificarRol($rolNombre)
    {
        $user = Auth::user();

        if (!$user) {
            Log::error('Usuario no autenticado al verificar rol: ' . $rolNombre);
            return false;
        }

        $user->load('roles');

        foreach ($user->roles as $rol) {
            if ($rol->nombre === $rolNombre) {
                return true;
            }
        }

        Log::warning('Rol no encontrado: ' . $rolNombre . ' para el usuario: ' . $user->id);
        return false;
    }

    // Obtener todos los roles
    public function index(Request $request)
    {
        $this->setTenantConnection($request);

        $roles = Rol::all();
        return response()->json($roles);
    }

    // Crear un nuevo rol
    public function store(Request $request)
    {
        $this->setTenantConnection($request);

        if ($this->verificarRol('Admin')) {
            $request->validate([
                'nombre' => 'required|unique:roles',
                'descripcion' => 'nullable|string',
            ]);

            $rol = Rol::create($request->all());
            return response()->json($rol, 201);
        } elseif ($this->verificarRol('Owner')) {
            

            // Verificar que la conexión al tenant se haya establecido
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
    public function show(Request $request, $id)
    {
        $this->setTenantConnection($request);

        $rol = Rol::findOrFail($id);
        return response()->json($rol);
    }

    // Actualizar un rol
    public function update(Request $request, $id)
    {
        $this->setTenantConnection($request);

        $rol = Rol::findOrFail($id);

        if ($this->verificarRol('Admin')) {
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

            // Verificar que la conexión al tenant se haya establecido
            if (!DB::connection('tenant')->getDatabaseName()) {
                return response()->json(['error' => 'No se pudo conectar a la base de datos del tenant'], 500);
            }

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
    public function destroy(Request $request, $id)
    {
        $this->setTenantConnection($request);

        // Prohibir eliminación de roles con id 1, 2, 3, 4
        if (in_array($id, [1, 2, 3, 4])) {
            return response()->json(['error' => 'No se puede eliminar este rol'], 403);
        }

        $rol = Rol::findOrFail($id);

        if ($this->verificarRol('Admin')) {
            $rol->delete();
            return response()->json(null, 204);
        } elseif ($this->verificarRol('Owner')) {
            $rol->delete();
            return response()->json(null, 204);
        } else {
            return response()->json(['error' => 'No tienes permisos para eliminar roles'], 403);
        }
    }
}
