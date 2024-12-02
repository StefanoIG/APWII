<?php

namespace App\Http\Controllers;

use App\Models\Permiso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PermisoController extends Controller
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

    // Obtener todos los permisos
    public function index(Request $request)
    {
        $this->setTenantConnection($request);

        $permisos = Permiso::all();
        return response()->json($permisos);
    }

    // Crear un nuevo permiso
    public function store(Request $request)
    {
        $this->setTenantConnection($request);

        // Verificar que el usuario tenga el rol necesario
        if ($this->verificarRol('Admin') || $this->verificarRol('Owner')) {
            $request->validate([
                'nombre' => 'required|unique:permisos',
                'descripcion' => 'nullable|string',
            ]);

            $permiso = Permiso::create($request->all());
            return response()->json($permiso, 201);
        }

        return response()->json(['error' => 'No tienes permisos para crear permisos'], 403);
    }

    // Mostrar un permiso específico
    public function show(Request $request, $id)
    {
        $this->setTenantConnection($request);

        $permiso = Permiso::findOrFail($id);
        return response()->json($permiso);
    }

    // Actualizar un permiso
    public function update(Request $request, $id)
    {
        $this->setTenantConnection($request);

        // No permitir actualizar permisos con ID entre 1 y 39
        if (in_array($id, range(1, 39))) {
            return response()->json(['error' => 'No puedes actualizar este permiso'], 403);
        }

        $permiso = Permiso::findOrFail($id);

        // Verificar que el usuario tenga el rol necesario
        if ($this->verificarRol('Admin') || $this->verificarRol('Owner')) {
            $request->validate([
                'nombre' => 'required|unique:permisos,nombre,' . $permiso->id,
                'descripcion' => 'nullable|string',
            ]);

            $permiso->update($request->all());
            return response()->json($permiso);
        }

        return response()->json(['error' => 'No tienes permisos para actualizar permisos'], 403);
    }

    // Eliminar un permiso
    public function destroy(Request $request, $id)
    {
        $this->setTenantConnection($request);

        // No permitir eliminar permisos con ID entre 1 y 39
        if (in_array($id, range(1, 39))) {
            return response()->json(['error' => 'No puedes eliminar este permiso'], 403);
        }

        $permiso = Permiso::findOrFail($id);

        // Verificar que el usuario tenga el rol necesario
        if ($this->verificarRol('Admin') || $this->verificarRol('Owner')) {
            $permiso->delete();
            return response()->json(null, 204);
        }

        return response()->json(['error' => 'No tienes permisos para eliminar permisos'], 403);
    }
}
