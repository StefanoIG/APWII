<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SitioController extends Controller
{
    // Función para verificar el rol del usuario (si es necesario)
    private function verificarRol($rol)
    {
        return Auth::user()->roles->contains('nombre', $rol);
    }

    /**
     * Establecer la conexión al tenant correspondiente usando el nombre de la base de datos.
     */
    protected function setTenantConnection(Request $request)
    {
        $tenantDatabase = $request->header('X-Tenant');

        if (!$tenantDatabase) {
            abort(400, 'El encabezado X-Tenant es obligatorio.');
        }

        // Configurar la conexión a la base de datos del tenant
        config(['database.connections.tenant' => [
            'driver' => 'sqlite',
            'database' => database_path('tenants/' . $tenantDatabase),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);

        // Purga la conexión anterior y reconecta con el tenant
        DB::purge('tenant');
        DB::reconnect('tenant');

        // Establecer el nombre de la conexión de forma predeterminada
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

        // Cargar roles y permisos del usuario
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
     * Crear un sitio.
     */
    public function store(Request $request)
    {
        // Configurar la conexión al tenant
        $this->setTenantConnection($request);

        if (!$this->verificarPermiso('Puede crear sitios')) {
            return response()->json(['error' => 'No tienes permiso para crear sitios'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nombre_sitio' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'ciudad' => 'required|string|max:255',
            'pais' => 'required|string|max:255',
            'id' => 'nullable|exists:usuarios,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $sitio = DB::connection('tenant')->table('sitio')->insertGetId([
                'nombre_sitio' => $request->nombre_sitio,
                'direccion' => $request->direccion,
                'ciudad' => $request->ciudad,
                'pais' => $request->pais,
                'created_by' => $request->id,
            ]);

            $sitioCreado = DB::connection('tenant')->table('sitio')->find($sitio);

            return response()->json([
                'message' => 'Sitio creado con éxito',
                'sitio' => $sitioCreado,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al crear el sitio: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Mostrar todos los sitios según el rol del usuario.
     */
    public function index(Request $request)
    {
        $this->setTenantConnection($request);

        $user = Auth::user();

        if ($this->verificarRol('Owner')) {
            $sitios = DB::connection('tenant')->table('sitio')->paginate(10);
        } else {
            $sitios = DB::connection('tenant')->table('sitio')->where('usuario_id', $user->id)->get();
        }

        return response()->json($sitios);
    }

    /**
     * Mostrar un sitio específico.
     */
    public function show(Request $request, $id)
    {
        $this->setTenantConnection($request);

        // if (!$this->verificarPermiso('Ver sitios')) {
        //     return response()->json(['error' => 'No tienes permiso para ver este sitio'], 403);
        // }

        $sitio = DB::connection('tenant')->table('sitio')->where('id_sitio', $id)->first();

        if (!$sitio) {
            return response()->json(['error' => 'Sitio no encontrado'], 404);
        }

        return response()->json($sitio);
    }

    /**
     * Actualizar la información de un sitio.
     */
    public function update(Request $request, $id)
    {
        $this->setTenantConnection($request);

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string',
            'direccion' => 'sometimes|required|string',
            'ciudad' => 'sometimes|required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // if (!$this->verificarPermiso('Editar sitios')) {
        //     return response()->json(['error' => 'No tienes permiso para editar este sitio'], 403);
        // }

        try {
            DB::connection('tenant')
            ->table('sitio')
            ->where('id_sitio', $id)
            ->update($request->only(['nombre', 'direccion', 'ciudad']));

            return response()->json(['message' => 'Sitio actualizado correctamente'], 200);
        } catch (\Exception $e) {
            Log::error('Error al actualizar sitio: ' . $e->getMessage());
            return response()->json(['error' => 'Error al actualizar sitio'], 500);
        }
    }

    /**
     * Eliminar un sitio.
     */
    public function destroy(Request $request, $id)
    {
        $this->setTenantConnection($request);

        // if (!$this->verificarPermiso('Eliminar sitios')) {
        //     return response()->json(['error' => 'No tienes permiso para eliminar este sitio'], 403);
        // }

        try {
            DB::connection('tenant')->table('sitio')->where('id_sitio', $id)->delete();
            return response()->json(['message' => 'Sitio eliminado correctamente'], 200);
        } catch (\Exception $e) {
            Log::error('Error al eliminar sitio: ' . $e->getMessage());
            return response()->json(['error' => 'Error al eliminar sitio'], 500);
        }
    }
}
