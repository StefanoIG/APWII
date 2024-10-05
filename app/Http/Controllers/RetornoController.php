<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Retorno;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;

class RetornoController extends Controller
{

    /**
     * Verifica si el usuario autenticado tiene un permiso específico.*/
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
    
    // Obtener todos los retornos
    public function index(Request $request)
    {
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

        if (!$this->verificarPermiso('Puede ver retornos')) {
            return response()->json(['error' => 'No tienes permiso para eliminar sitios'], 403);
        }

        try {
            $retornos = Retorno::all();
            return response()->json($retornos);
        } catch (\Exception $e) {
            Log::error('Error al obtener retornos: ' . $e->getMessage());
            return response()->json(['error' => 'Error en el servidor'], 500);
        }
    }

    // Crear un nuevo retorno
    public function store(Request $request)
    {
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

        try {
            $validatedData = $request->validate([
                'id_comprobante' => 'required|exists:comprobante,id_comprobante',
                'id_producto' => 'required|exists:producto,id_producto',
                'fecha_retorno' => 'required|date',
                'cantidad' => 'required|integer',
                'motivo_retorno' => 'required|string|max:255',
                'estado_retorno' => 'required|string|max:255',
                'isActive' => 'required|boolean'
            ]);

            $retorno = Retorno::create($validatedData);

            return response()->json($retorno, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error al crear retorno: ' . $e->getMessage());
            return response()->json(['error' => 'Error en el servidor', 'details' => $e->getMessage()], 500);
        }
    }

    // Mostrar un retorno específico
    public function show(string $id, Request $request)
    {
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
        // Obtener el retorno
        $retorno = Retorno::find($id);

        if (!$retorno) {
            return response()->json(['error' => 'Retorno no encontrado'], 404);
        }

        return response()->json($retorno);
    }


    //Paginacion del index
    public function paginatedIndex(Request $request)
    {
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

        // Validar la solicitud
        $validator = Validator::make($request->all(), [
            'id_comprobante' => 'sometimes|exists:comprobante,id_comprobante',
            'id_producto' => 'sometimes|exists:producto,id_producto',
            'fecha_retorno' => 'sometimes|date',
            'cantidad' => 'sometimes|integer',
            'motivo_retorno' => 'sometimes|string|max:255',
            'estado_retorno' => 'sometimes|string|max:255',
            'deleted_at' => 'sometimes|boolean', // Use boolean to filter active/inactive
            'per_page' => 'sometimes|integer|min:1|max:100', // Limitar el número de resultados por página
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Obtener los datos validados
        $validatedData = $validator->validated();

        // Construir la consulta con los filtros opcionales
        $query = Retorno::query();

        if (isset($validatedData['id_comprobante'])) {
            $query->where('id_comprobante', $validatedData['id_comprobante']);
        }

        if (isset($validatedData['id_producto'])) {
            $query->where('id_producto', $validatedData['id_producto']);
        }

        if (isset($validatedData['fecha_retorno'])) {
            $query->whereDate('fecha_retorno', $validatedData['fecha_retorno']);
        }

        if (isset($validatedData['cantidad'])) {
            $query->where('cantidad', $validatedData['cantidad']);
        }

        if (isset($validatedData['motivo_retorno'])) {
            $query->where('motivo_retorno', 'like', '%' . $validatedData['motivo_retorno'] . '%');
        }

        if (isset($validatedData['estado_retorno'])) {
            $query->where('estado_retorno', 'like', '%' . $validatedData['estado_retorno'] . '%');
        }

        // Filtrar por deleted_at
        if (isset($validatedData['deleted_at'])) {
            if ($validatedData['deleted_at']) {
                $query->onlyTrashed(); // Solo retornos eliminados
            } else {
                $query->withTrashed(); // Todos los retornos, incluidos los eliminados
            }
        }

        // Obtener el número de resultados por página, por defecto 15
        $perPage = $validatedData['per_page'] ?? 15;

        // Obtener los resultados paginados
        $retornos = $query->paginate($perPage);

        return response()->json($retornos);
    }
}
