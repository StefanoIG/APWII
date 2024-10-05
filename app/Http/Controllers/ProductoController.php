<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\Usuario;

class ProductoController extends Controller
{
    /**
     * Verifica si el usuario autenticado tiene un permiso específico.
     *
     * @param string $permisoNombre
     * @return bool
     */
    private function verificarPermiso($permisoNombre)
    {
        $user = Auth::user();
        if ($user === null) {
            Log::error('El usuario no está autenticado.');
            return false;
        }

        $roles = $user->roles;

        // Log para ver los roles del usuario
        Log::info('Roles del usuario:', ['roles' => $roles]);

        foreach ($roles as $rol) {
            $tienePermiso = $rol->permisos()->where('nombre', $permisoNombre)->exists();
            Log::info('Verificando permiso:', ['rol' => $rol->nombre, 'tiene_permiso' => $tienePermiso]);
            if ($tienePermiso) {
                return true;
            }
        }

        return false;
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
        if ($user === null) {
            Log::error('El usuario no está autenticado.');
            return false;
        }

        $roles = $user->roles;

        foreach ($roles as $rol) {
            if ($rol->nombre === $rolNombre) {
                return true;
            }
        }

        return false;
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

    /**
     * Display a listing of the resource.
     */
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
        return Producto::all();
    }

    /**
     * Store a newly created resource in storage.
     */
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

        // Verificar si el usuario está autenticado
        $user = Auth::user();
        if ($user === null) {
            Log::warning('El usuario no está autenticado al intentar crear un producto.');
            return response()->json(['error' => 'No estás autenticado.'], 401);
        }

        // Verificar permisos
        if (!$this->verificarPermiso('Puede crear productos')) {
            Log::warning('Permiso denegado para crear productos para el usuario:', ['user_id' => $user->id]);
            return response()->json(['error' => 'No tienes permiso para crear productos'], 403);
        }

        // Validar los datos enviados en la solicitud
        $validator = Validator::make($request->all(), [
            'nombre_producto' => 'required|string|max:255',
            'tipo_producto' => 'required|string|max:255',
            'descripcion_producto' => 'required|string|max:255',
            'precio' => 'required|numeric',
            'id_etiqueta' => 'integer|nullable',
            'isActive' => 'required|boolean',
        ]);

        // Si la validación falla
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {

            // Crear el producto en la tabla productos
            $producto = Producto::create([
                'nombre_producto' => $request->nombre_producto,
                'tipo_producto' => $request->tipo_producto,
                'descripcion_producto' => $request->descripcion_producto,
                'precio' => $request->precio,
                'id_etiqueta' => $request->id_etiqueta,
                'isActive' => $request->isActive,
            ]);

            // Obtener los correos de los administradores directamente desde la tabla usuario_rol
            $adminEmail = Usuario::whereHas('roles', function ($query) {
                $query->where('nombre', 'Admin');
            })->pluck('correo_electronico')->toArray();

            // Verificar que haya correos de administradores
            if (empty($adminEmail)) {
                throw new \Exception("No hay administradores disponibles para notificar sobre la creación del producto.");
            }

            // Enviar un correo a los administradores notificando la creación del producto
            Mail::raw('Se ha creado un nuevo producto.', function ($message) use ($adminEmail) {
                $message->to($adminEmail)
                    ->subject('Nuevo Producto Creado');
            });

            // Generar el código de barras y asignarlo al producto
            $codigoBarra = $this->generarCodigoDeBarras();
            $producto->codigo_barra = $codigoBarra;
            $producto->save();

            DB::commit();

            return response()->json(['message' => 'Producto creado y notificación enviada correctamente.'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            // Registrar el error en los logs
            Log::error('Error al crear el producto: ' . $e->getMessage());
            return response()->json(['errors' => 'Hubo un error al crear el producto. Por favor, inténtelo de nuevo.'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
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

        return Producto::find($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
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

        // Verificar permiso
        if (!$this->verificarPermiso('Puede actualizar productos')) {
            return response()->json(['error' => 'No tienes permiso para actualizar productos'], 403);
        }

        // Validar la solicitud
        $validator = Validator::make($request->all(), [
            'nombre_producto' => 'sometimes|required|string|max:255',
            'tipo_producto' => 'sometimes|required|string|max:255',
            'descripcion_producto' => 'sometimes|required|string|max:255',
            'precio' => 'sometimes|required|numeric',
            'id_etiqueta' => 'integer|nullable',
            'isActive' => 'sometimes|required|boolean',
        ]);

        // Si la validación falla
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        DB::beginTransaction();
        try {
            // Buscar el producto por ID
            $producto = Producto::findOrFail($id);

            // Actualizar el producto
            $producto->update($request->all());

            DB::commit();
            return response()->json($producto, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar producto: ' . $e->getMessage());
            return response()->json(['error' => 'Error al actualizar el producto'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
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

        // Verificar permiso
        if (!$this->verificarPermiso('Puede eliminar productos')) {
            return response()->json(['error' => 'No tienes permiso para eliminar productos'], 403);
        }

        // Buscar el producto por ID
        $producto = Producto::find($id);

        // Si el producto no existe
        if (!$producto) {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }

        // Eliminar el producto
        $producto->delete();

        return response()->json(['message' => 'Producto eliminado correctamente'], 200);
    }

    public function verCodigoDeBarras($id, Request $request)
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

        // Buscar el producto por ID
        $producto = Producto::findOrFail($id);

        // Verificar si el producto tiene un código de barras
        if (!$producto->codigo_barra) {
            return response()->json(['error' => 'El producto no tiene un código de barras asignado.'], 404);
        }

        // Generar la imagen del código de barras a partir del código de la base de datos
        $generatorPNG = new BarcodeGeneratorPNG();
        $image = $generatorPNG->getBarcode($producto->codigo_barra, $generatorPNG::TYPE_CODE_128);

        // Devolver la imagen como respuesta
        return response($image)->header('Content-type', 'image/png');
    }

    private function generarCodigoDeBarras()
    {
        // Generar un número aleatorio de 8 dígitos
        $codigoBarra = str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);

        return $codigoBarra;
    }

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
            'nombre_producto' => 'sometimes|string|max:255',
            'tipo_producto' => 'sometimes|string|max:255',
            'descripcion_producto' => 'sometimes|string|max:255',
            'precio' => 'sometimes|numeric',
            'id_etiqueta' => 'sometimes|integer|nullable',
            'deleted_at' => 'sometimes|boolean', // Use boolean to filter active/inactive
            'per_page' => 'sometimes|integer|min:1|max:100', // Limitar el número de resultados por página
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Obtener los datos validados
        $validatedData = $validator->validated();

        // Construir la consulta con los filtros opcionales
        $query = Producto::with('etiquetas'); // Incluye las etiquetas relacionadas

        if (isset($validatedData['nombre_producto'])) {
            $query->where('nombre_producto', 'like', '%' . $validatedData['nombre_producto'] . '%');
        }

        if (isset($validatedData['tipo_producto'])) {
            $query->where('tipo_producto', 'like', '%' . $validatedData['tipo_producto'] . '%');
        }

        if (isset($validatedData['descripcion_producto'])) {
            $query->where('descripcion_producto', 'like', '%' . $validatedData['descripcion_producto'] . '%');
        }

        if (isset($validatedData['precio'])) {
            $query->where('precio', $validatedData['precio']);
        }

        if (isset($validatedData['id_etiqueta'])) {
            // Filtrar productos que tengan una etiqueta específica
            $query->whereHas('etiquetas', function ($q) use ($validatedData) {
                $q->where('etiqueta_id', $validatedData['id_etiqueta']);
            });
        }

        // Filtrar por deleted_at
        if (isset($validatedData['deleted_at'])) {
            if ($validatedData['deleted_at']) {
                $query->onlyTrashed(); // Solo productos eliminados
            } else {
                $query->withTrashed(); // Todos los productos, incluidos los eliminados
            }
        }

        // Obtener el número de resultados por página, por defecto 15
        $perPage = $validatedData['per_page'] ?? 15;

        // Obtener los resultados paginados
        $productos = $query->paginate($perPage);

        return response()->json($productos);
    }
}
