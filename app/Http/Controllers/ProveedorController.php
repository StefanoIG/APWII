<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProveedorController extends Controller
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
     * Verificar si el usuario tiene un rol específico.
     */
    private function verificarRol($rolNombre)
    {
        return Auth::user()->roles->contains('nombre', $rolNombre);
    }

    /**
     * Mostrar una lista de proveedores.
     */
    public function index(Request $request)
    {
        $this->setTenantConnection($request);

        //if (!$this->verificarPermiso('Puede ver proveedores')) {
            //return response()->json(['error' => 'No tienes permiso para ver proveedores'], 403);
        //}

        $proveedores = DB::connection('tenant')->table('proveedor')->get();

        return response()->json($proveedores, 200);
    }



    /**
     * Crear un proveedor.
     */
    public function store(Request $request)
    {
        $this->setTenantConnection($request);

        // if (!$this->verificarPermiso('Puede crear proveedores')) {
        //     return response()->json(['error' => 'No tienes permiso para crear proveedores'], 403);
        // }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'Cuidad' => 'required|string|max:255',
        ]);


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            Log::info('Insertando proveedor en la base de datos del tenant.');

            $proveedorId = DB::connection('tenant')->table('proveedor')->insertGetId([
                'nombre' => $request->nombre,
                'direccion' => $request->direccion,
                'telefono' => $request->telefono,
                'email' => $request->email,
                'Cuidad' => $request->Cuidad,
            ]);

            dd($proveedorId);

            $proveedor = DB::connection('tenant')->table('proveedor')->find($proveedorId);

            return response()->json(['message' => 'Proveedor creado con éxito', 'proveedor' => $proveedor], 201);
        } catch (\Exception $e) {
            Log::error('Error al crear proveedor: ' . $e->getMessage());
            return response()->json(['error' => 'Error al crear proveedor'], 500);
        }
    }

    /**
     * Mostrar un proveedor específico.
     */
    public function show(Request $request, $id)
    {
        $this->setTenantConnection($request);

        $proveedor = DB::connection('tenant')->table('proveedor')->where('id_proveedor', $id)->first();

        if (!$proveedor) {
            return response()->json(['error' => 'Proveedor no encontrado'], 404);
        }

        return response()->json($proveedor);
    }

    /**
     * Actualizar la información de un proveedor.
     */
    public function update(Request $request, $id)
    {
        $this->setTenantConnection($request);

        // if (!$this->verificarPermiso('Puede editar proveedores')) {
        //     return response()->json(['error' => 'No tienes permiso para editar proveedores'], 403);
        // }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'Cuidad' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::connection('tenant')->table('proveedor')->where('id_proveedor', $id)->update($request->only(['nombre', 'direccion', 'telefono', 'email']));
            return response()->json(['message' => 'Proveedor actualizado correctamente'], 200);
        } catch (\Exception $e) {
            Log::error('Error al actualizar proveedor: ' . $e->getMessage());
            return response()->json(['error' => 'Error al actualizar proveedor'], 500);
        }
    }

    /**
     * Eliminar un proveedor.
     */
    public function destroy(Request $request, $id)
    {
        $this->setTenantConnection($request);

        // if (!$this->verificarPermiso('Puede eliminar proveedores')) {
        //     return response()->json(['error' => 'No tienes permiso para eliminar proveedores'], 403);
        // }

        try {
            DB::connection('tenant')->table('proveedor')->where('id_proveedor', $id)->delete();
            return response()->json(['message' => 'Proveedor eliminado correctamente'], 200);
        } catch (\Exception $e) {
            Log::error('Error al eliminar proveedor: ' . $e->getMessage());
            return response()->json(['error' => 'Error al eliminar proveedor'], 500);
        }
    }
}
