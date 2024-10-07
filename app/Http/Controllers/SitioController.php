<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sitio;
use Illuminate\Support\Facades\Validator;
use App\Models\Lote;
use Illuminate\Support\Facades\Auth;
use App\Models\Usuario;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


class SitioController extends Controller
{

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


    private function verificarPermiso($permisoNombre)
    {
        $user = Auth::user();
        if (!$user) {
            Log::error('Usuario no autenticado al verificar permiso: ' . $permisoNombre);
            return false;
        }
        $user->load('roles.permisos');

        // Verificar si el usuario está autenticado
        if (!$user) {
            Log::error('Usuario no autenticado al verificar permiso: ' . $permisoNombre);
            return false; // O manejar el error según sea necesario
        }

        // Obtener los roles asociados al usuario
        $roles = $user->roles;

        // Comprobar si el usuario tiene roles
        if ($roles->isEmpty()) {
            Log::error('Usuario no tiene roles al verificar permiso: ' . $permisoNombre);
            return false; // Si no tiene roles, no tiene permisos
        }

        // Iterar sobre cada rol del usuario
        foreach ($roles as $rol) {
            Log::info('Verificando rol: ' . $rol->nombre);
            // Verificar si el rol tiene el permiso requerido
            $tienePermiso = $rol->permisos()->where('nombre', $permisoNombre)->exists();
            if ($tienePermiso) {
                Log::info('Permiso encontrado: ' . $permisoNombre . ' para el usuario: ' . $user->id);
                return true; // Si encuentra el permiso, devuelve true
            }
        }

        Log::error('Permiso no encontrado: ' . $permisoNombre . ' para el usuario: ' . $user->id);
        return false; // Si no encuentra el permiso, devuelve false
    }

    private function verificarRol($rolNombre)
    {
        $user = Auth::user();

        // Verificar si el usuario está autenticado
        if (!$user) {
            return false; // O manejar el error según sea necesario
        }

        // Obtener los roles asociados al usuario
        $roles = $user->roles;

        // Verificar si alguno de los roles coincide con el nombre del rol requerido
        foreach ($roles as $rol) {
            if ($rol->nombre === $rolNombre) {
                return true;
            }
        }

        return false;
    }



    // Mostrar todos los sitios según el rol del usuario
    public function index(Request $request)
    {
        // Validar que se haya enviado el nombre de la base de datos del tenant
        $validator = Validator::make($request->all(), [
            'tenant_database' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Conectar a la base de datos del tenant
        try {
            $this->setTenantConnection($request->tenant_database);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        $user = Auth::user();

        if ($this->verificarRol('Empleado')) {
            $sitios = Sitio::where('sitio_id', $user->sitio_id)->get();
            return response()->json($sitios);
        }

        if ($this->verificarRol('Owner')) {
            $sitios = Sitio::where('created_by', $user->id)->get();
            return response()->json($sitios);
        }

        if ($this->verificarRol('Admin')) {
            $sitios = Sitio::all();
            return response()->json($sitios);
        }

        return response()->json(['error' => 'No tienes permiso para ver esta información'], 403);
    }


    // Mostrar un sitio específico por ID
    public function show(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'tenant_database' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $this->setTenantConnection($request->tenant_database);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        $sitio = Sitio::find($id);
        if (!$sitio) {
            return response()->json(['error' => 'Sitio no encontrado'], 404);
        }

        if (
            $this->verificarRol('Admin') ||
            $this->verificarPermiso('Puede ver informacion de todos los usuarios') ||
            $this->verificarPermiso('Puede ver informacion usuarios de un solo sitio')
        ) {
            return response()->json($sitio);
        }

        return response()->json(['error' => 'No tienes permiso para ver esta información'], 403);
    }


    // Crear un sitio
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tenant_database' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $this->setTenantConnection($request->tenant_database);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

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

        $sitio = Sitio::create([
            'nombre_sitio' => $request->nombre_sitio,
            'direccion' => $request->direccion,
            'ciudad' => $request->ciudad,
            'pais' => $request->pais,
            'created_by' => $request->id,
        ]);

        return response()->json(['message' => 'Sitio creado con éxito', 'sitio' => $sitio], 201);
    }


    // Actualizar un sitio
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'tenant_database' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $this->setTenantConnection($request->tenant_database);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        $sitio = Sitio::find($id);
        if (!$sitio) {
            return response()->json(['error' => 'Sitio no encontrado'], 404);
        }

        if (!$this->verificarPermiso('Puede actualizar sitios')) {
            return response()->json(['error' => 'No tienes permiso para actualizar sitios'], 403);
        }

        $validatedData = $request->validate([
            'nombre_sitio' => 'sometimes|required|string|max:255',
            'direccion' => 'sometimes|required|string|max:255',
            'ciudad' => 'sometimes|required|string|max:255',
            'pais' => 'sometimes|required|string|max:255',
        ]);

        $sitio->update($validatedData);

        return response()->json(['message' => 'Sitio actualizado con éxito', 'sitio' => $sitio]);
    }



    // Eliminar un sitio
    public function destroy(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'tenant_database' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $this->setTenantConnection($request->tenant_database);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        $sitio = Sitio::find($id);
        if (!$sitio) {
            return response()->json(['error' => 'Sitio no encontrado'], 404);
        }

        if (!$this->verificarPermiso('Puede borrar sitios')) {
            return response()->json(['error' => 'No tienes permiso para eliminar sitios'], 403);
        }

        $sitio->delete();

        return response()->json(['message' => 'Sitio eliminado con éxito'], 200);
    }


    // Index paginado
    // Paginación de sitios según el rol del usuario
    public function paginatedIndex(Request $request)
    {
        // Validar que se haya enviado el nombre de la base de datos
        $validator = Validator::make($request->all(), [
            'tenant_database' => 'required|string', // Validar que el nombre de la base de datos del tenant se haya enviado
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Obtener el nombre de la base de datos del tenant desde el request
        $tenantDatabase = $request->tenant_database;

        // Establecer la conexión al tenant usando el nombre de la base de datos
        $this->setTenantConnection($tenantDatabase);

        // Verificar que la conexión al tenant está activa
        if (!DB::connection('tenant')->getDatabaseName()) {
            return response()->json(['error' => 'No se pudo conectar a la base de datos del tenant'], 500);
        }

        // Verificar si el usuario está autenticado
        $authUser = Auth::user();
        if (!$authUser) {
            return response()->json(['errors' => 'No estás autenticado.'], 401);
        }

        $user = $authUser;

        // Validar los filtros solicitud
        $validator = Validator::make($request->all(), [
            'nombre_sitio' => 'sometimes|string|max:255',
            'direccion' => 'sometimes|string|max:255',
            'ciudad' => 'sometimes|string|max:255',
            'pais' => 'sometimes|string|max:255',
            'deleted_at' => 'sometimes|boolean',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Obtener los datos validados
        $validatedData = $validator->validated();

        // Construir la consulta con los filtros opcionales
        $query = Sitio::query();

        if (isset($validatedData['nombre_sitio'])) {
            $query->where('nombre_sitio', 'like', '%' . $validatedData['nombre_sitio'] . '%');
        }

        if (isset($validatedData['direccion'])) {
            $query->where('direccion', 'like', '%' . $validatedData['direccion'] . '%');
        }

        if (isset($validatedData['ciudad'])) {
            $query->where('ciudad', 'like', '%' . $validatedData['ciudad'] . '%');
        }

        if (isset($validatedData['pais'])) {
            $query->where('pais', 'like', '%' . $validatedData['pais'] . '%');
        }

        // Filtrar por deleted_at
        if (isset($validatedData['deleted_at'])) {
            if ($validatedData['deleted_at']) {
                $query->onlyTrashed(); // Solo sitios eliminados
            } else {
                $query->withTrashed(); // Todos los sitios, incluidos los eliminados
            }
        }

        // Si el usuario es empleado, filtrar por sitio_id
        if ($this->verificarRol('Empleado')) {
            $query->where('sitio_id', $user->sitio_id);
        }

        // Si el usuario es owner, filtrar por created_by
        if ($this->verificarRol('Owner')) {
            $query->where('created_by', $user->id);
        }

        // Obtener el número de resultados por página, por defecto 15
        $perPage = $validatedData['per_page'] ?? 15;

        // Obtener los resultados paginados
        $sitios = $query->paginate($perPage);

        return response()->json($sitios);
    }
}
