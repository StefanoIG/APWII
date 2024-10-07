<?php

namespace App\Http\Controllers;

use App\Models\Etiqueta;
use App\Models\Producto;
use App\Models\Lote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class EtiquetaController extends Controller
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

    /**
     * Verifica si el usuario autenticado tiene un permiso específico.
     *
     * @param string $permisoNombre
     * @return bool
     */
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

    /**
     * Verifica si el usuario autenticado tiene un rol específico.
     *
     * @param string $rolNombre
     * @return bool
     */
    private function verificarRol($rolNombre)
    {
        $user = Auth::user();
        $roles = $user->roles;

        foreach ($roles as $rol) {
            if ($rol->nombre === $rolNombre) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtener todas las etiquetas.
     */
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

        // if (!$this->verificarPermiso('Puede ver etiquetas')) {
        //     return response()->json(['error' => 'No tienes permiso para ver etiquetas'], 403);
        // }

        $etiquetas = Etiqueta::all();
        return response()->json($etiquetas, 200);
    }
    /**
     * Crear una nueva etiqueta.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'color_hex' => 'required|string|max:7',  // Validar código HEX
            'descripcion' => 'nullable|string',
            'categoria' => 'required|string|max:255',
            'prioridad' => 'required|in:alta,media,baja',
            'isActive' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $etiqueta = Etiqueta::create($request->all());

        return response()->json(['message' => 'Etiqueta creada exitosamente', 'etiqueta' => $etiqueta], 201);
    }

    /**
     * Actualizar una etiqueta existente.
     */
    public function update(Request $request, $id)
    {

        if (!$this->verificarPermiso('Puede actualizar etiquetas')) {
            return response()->json(['error' => 'No tienes permiso para actualizar etiquetas'], 403);
        }

        $etiqueta = Etiqueta::findOrFail($id);

        $validatedData = $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'color_hex' => 'sometimes|required|string|max:7',
            'descripcion' => 'nullable|string',
            'categoria' => 'sometimes|required|string|max:255',
            'prioridad' => 'sometimes|required|in:alta,media,baja',
            'isActive' => 'boolean'
        ]);

        $etiqueta->update($validatedData);

        return response()->json(['message' => 'Etiqueta actualizada exitosamente', 'etiqueta' => $etiqueta], 200);
    }

    /**
     * Obtener una etiqueta específica.
     */
    public function show($id)
    {


        $etiqueta = Etiqueta::findOrFail($id);
        return response()->json($etiqueta, 200);
    }

    /**
     * Eliminar una etiqueta existente.
     */
    public function destroy($id)
    {
        if (!$this->verificarPermiso('Puede borrar etiquetas')) {
            return response()->json(['error' => 'No tienes permiso para eliminar etiquetas'], 403);
        }

        $etiqueta = Etiqueta::findOrFail($id);
        $etiqueta->delete();

        return response()->json(['message' => 'Etiqueta eliminada exitosamente'], 200);
    }

    /**
     * Obtener etiquetas con paginación.
     */
    public function paginatedIndex(Request $request)
    {
        if (!$this->verificarPermiso('Puede ver etiquetas')) {
            return response()->json(['error' => 'No tienes permiso para ver etiquetas'], 403);
        }

        // Validar la solicitud
        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|string|max:255',
            'color_hex' => 'sometimes|string|max:7',  // Validar código HEX
            'descripcion' => 'sometimes|string|nullable',
            'categoria' => 'sometimes|string|max:255',
            'prioridad' => 'sometimes|in:alta,media,baja',
            'deleted_at' => 'sometimes|boolean', // Use boolean to filter active/inactive
            'per_page' => 'sometimes|integer|min:1|max:100', // Limitar el número de resultados por página
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Obtener los datos validados
        $validatedData = $validator->validated();

        // Construir la consulta con los filtros opcionales
        $query = Etiqueta::query();

        if (isset($validatedData['nombre'])) {
            $query->where('nombre', 'like', '%' . $validatedData['nombre'] . '%');
        }

        if (isset($validatedData['color_hex'])) {
            $query->where('color_hex', 'like', '%' . $validatedData['color_hex'] . '%');
        }

        if (isset($validatedData['descripcion'])) {
            $query->where('descripcion', 'like', '%' . $validatedData['descripcion'] . '%');
        }

        if (isset($validatedData['categoria'])) {
            $query->where('categoria', 'like', '%' . $validatedData['categoria'] . '%');
        }

        if (isset($validatedData['prioridad'])) {
            $query->where('prioridad', $validatedData['prioridad']);
        }

        // Filtrar por deleted_at
        if (isset($validatedData['deleted_at'])) {
            if ($validatedData['deleted_at']) {
                $query->onlyTrashed(); // Solo etiquetas eliminadas
            } else {
                $query->withTrashed(); // Todas las etiquetas, incluidas las eliminadas
            }
        }

        // Obtener el número de resultados por página, por defecto 15
        $perPage = $validatedData['per_page'] ?? 15;

        // Obtener los resultados paginados
        $etiquetas = $query->paginate($perPage);

        return response()->json($etiquetas);
    }

    /**
     * Asignar una etiqueta a un producto.
     */
    public function asignarEtiquetaProducto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'producto_id' => 'required|exists:producto,id_producto',
            'etiqueta_id' => 'required|exists:etiqueta,id_etiqueta'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $producto = Producto::findOrFail($request->input('producto_id'));
        $producto->etiquetas()->attach($request->input('etiqueta_id'));

        return response()->json(['message' => 'Etiqueta asignada al producto exitosamente'], 200);
    }

    /**
     * Asignar una etiqueta a un lote.
     */
    public function asignarEtiquetaLote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lote_id' => 'required|exists:lote,id_lote',
            'etiqueta_id' => 'required|exists:etiqueta,id_etiqueta'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $lote = Lote::findOrFail($request->input('lote_id'));
        $lote->etiquetas()->attach($request->input('etiqueta_id'));

        return response()->json(['message' => 'Etiqueta asignada al lote exitosamente'], 200);
    }
}
