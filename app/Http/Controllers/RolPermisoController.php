<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Permiso;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RolPermisoController extends Controller
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

    /**
     * Asignar un permiso a un rol.
     */
    public function store(Request $request)
    {
        $this->setTenantConnection($request);

        // Verificar si el usuario tiene el rol adecuado
        if ($this->verificarRol('Admin') || $this->verificarRol('Owner')) {
            $request->validate([
                'rol_id' => 'required|exists:roles,id',
                'permiso_id' => 'required|exists:permisos,id',
            ]);

            $rol = Rol::findOrFail($request->rol_id);
            $permiso = Permiso::findOrFail($request->permiso_id);

            // Verificar si el permiso tiene un ID que no está permitido
            $permisosNoPermitidos = [2, 3, 4, 15, 16, 20, 21, 22, 23, 24, 25, 26, 27, 39];
            if (in_array($permiso->id, $permisosNoPermitidos)) {
                return response()->json(['error' => 'Este permiso no puede ser asignado'], 403);
            }

            $rol->permisos()->attach($permiso);
            return response()->json(['message' => 'Permiso asignado al rol correctamente']);
        }

        return response()->json(['error' => 'No tienes permiso para asignar permisos'], 403);
    }

    /**
     * Eliminar un permiso de un rol.
     */
    public function destroy(Request $request, $permisoId)
    {
        $this->setTenantConnection($request);

        // Verificar si el usuario tiene el rol adecuado
        if ($this->verificarRol('Admin') || $this->verificarRol('Owner')) {
            $request->validate([
                'rol_id' => 'required|exists:roles,id',
            ]);

            $rol = Rol::findOrFail($request->rol_id);
            $permiso = Permiso::findOrFail($permisoId);

            // No permitir eliminar permisos con ID entre 1 y 78
            if ($permiso->id <= 78) {
                return response()->json(['error' => 'Este permiso no puede ser removido del rol'], 403);
            }

            // Eliminar el permiso del rol
            $rol->permisos()->detach($permiso);
            return response()->json(['message' => 'Permiso removido del rol correctamente']);
        }

        return response()->json(['error' => 'No tienes permiso para eliminar permisos'], 403);
    }


    /**
     * Obtener los permisos de un rol.
     */
    public function show($rolId)
    {
        $rol = Rol::findOrFail($rolId);
        $permisos = $rol->permisos;
        return response()->json($permisos);
    }

    /**
     * Listar todos los roles y sus permisos.
     */
    public function index(Request $request)
    {
        $this->setTenantConnection($request);

        $roles = Rol::with('permisos')->get();
        return response()->json($roles);
    }
}
