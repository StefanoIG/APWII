<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Proveedor;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\login;

class ProveedorController extends Controller
{

        private function verificarPermiso($permisoNombre)
    {
        $user = Auth::guard('api')->user();
        if (!$user) {
            Log::error('Usuario no autenticado en verificarPermiso. Token: ' . request()->header('Authorization'));
            return response()->json(['error' => 'No estás autenticado'], 403);
        }

        // Continuar con la lógica del controlador si el usuario está autenticado
        Log::info('Usuario autenticado en verificarPermiso: ' . $user->id);

        // Si el usuario es admin, otorgarle todos los permisos automáticamente
        if ($this->verificarRol('Admin')) {
            Log::info('Permiso concedido automáticamente a Admin: ' . $user->id);
            return true;
        }

        $roles = $user->roles;

        foreach ($roles as $rol) {
            if ($rol->permisos()->where('nombre', $permisoNombre)->exists()) {
                return true;
            }
        }

        Log::warning('Permiso no encontrado: ' . $permisoNombre . ' para el usuario: ' . $user->id);
        return false;
    }

    // Verifica si el usuario tiene un rol específico
    private function verificarRol($rolNombre)
    {
        $user = Auth::guard('api')->user();
        if (!$user) {
            Log::error('Usuario no autenticado en verificarRol. Token: ' . request()->header('Authorization'));
            return false;
        }
        
        Log::info('Usuario autenticado en verificarRol: ' . $user->id);

        $roles = $user->roles;

        foreach ($roles as $rol) {
            if ($rol->nombre === $rolNombre) {
                return true;
            }
        }

        Log::warning('Rol no encontrado: ' . $rolNombre . ' para el usuario: ' . $user->id);
        return false;
    }

   /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::guard('api')->user();
        if (!$user) {
            Log::error('Usuario no autenticado en index. Token: ' . request()->header('Authorization'));
            return response()->json(['error' => 'No estás autenticado'], 403);
        }
    
        Log::info('Usuario autenticado en index: ' . $user->id);
    
        // Si el usuario es un empleado, filtrar los proveedores relacionados con su sitio_id
        if ($this->verificarRol('Empleado')) {
            $proveedores = Proveedor::where('sitio_id', $user->sitio_id)->get();
            Log::info('Proveedores filtrados por sitio_id para el usuario: ' . $user->id);
            return response()->json($proveedores);
        }
    
        // Si el usuario es owner, filtrar los proveedores creados por el usuario
        if ($this->verificarRol('Owner')) {
            $proveedores = Proveedor::where('created_by', $user->id)->get();
            Log::info('Proveedores filtrados por created_by para el usuario: ' . $user->id);
            return response()->json($proveedores);
        }
    
        // Si el usuario es admin, puede ver todos los proveedores
        if ($this->verificarRol('Admin')) {
            $proveedores = Proveedor::all();
            Log::info('Todos los proveedores mostrados para el usuario admin: ' . $user->id);
            return response()->json($proveedores);
        }
    
        // Si no tiene ningún rol relevante, devolver un error
        Log::warning('Usuario sin permisos relevantes en index: ' . $user->id);
        return response()->json(['error' => 'No tienes permiso para ver esta información'], 403);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!$this->verificarPermiso('Puede crear proveedores')) {
            return response()->json(['error' => 'No tienes permiso para crear proveedores'], 403);
        }

        // Validación de los datos
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'email' => 'required|string|max:255',
            'telefono' => 'required|string|max:255',
            'Cuidad' => 'required|string|max:255',
            'Activo' => 'required|boolean',
            'isActive' => 'required|boolean',
        ]);

        // Si la validación falla
        if ($validator->fails()) {
            Log::error('Validación fallida en store: ' . json_encode($validator->errors()));
            return response()->json($validator->errors(), 400);
        }

        // Crear el proveedor
        $proveedor = Proveedor::create($request->all());
        Log::info('Proveedor creado correctamente: ' . $proveedor->id);
        return response()->json(['message' => 'Proveedor creado correctamente', $proveedor], 200);
    }

/**
 * Display the specified resource.
 */
public function show(string $id)
{
    $user = Auth::user();
    $proveedor = Proveedor::find($id);

    if (!$proveedor) {
        Log::error('Proveedor no encontrado en show: ' . $id);
        return response()->json(['error' => 'Proveedor no encontrado'], 404);
    }

    // Verificar permisos de visualización
    if ($this->verificarRol('Empleado') && $proveedor->sitio_id !== $user->sitio_id) {
        Log::warning('Permiso denegado en show para el usuario: ' . $user->id);
        return response()->json(['error' => 'No tienes permiso para ver este proveedor'], 403);
    }

    if ($this->verificarRol('Owner') && $proveedor->created_by !== $user->id) {
        Log::warning('Permiso denegado en show para el usuario: ' . $user->id);
        return response()->json(['error' => 'No tienes permiso para ver este proveedor'], 403);
    }

    Log::info('Proveedor mostrado correctamente: ' . $proveedor->id);
    return response()->json($proveedor);
}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $proveedor = Proveedor::find($id);

        if (!$proveedor) {
            Log::error('Proveedor no encontrado en update: ' . $id);
            return response()->json(['error' => 'Proveedor no encontrado'], 404);
        }

        // Verificar permisos de edición
        if ($this->verificarRol('Empleado') || ($this->verificarRol('Owner') && $proveedor->created_by !== Auth::user()->id)) {
            Log::warning('Permiso denegado en update para el usuario: ' . Auth::user()->id);
            return response()->json(['error' => 'No tienes permiso para actualizar este proveedor'], 403);
        }

        // Validación de los datos
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'email' => 'required|string|max:255',
            'telefono' => 'required|string|max:255',
            'Cuidad' => 'required|string|max:255',
            'Activo' => 'required|boolean',
        ]);

        // Si la validación falla
        if ($validator->fails()) {
            Log::error('Validación fallida en update: ' . json_encode($validator->errors()));
            return response()->json($validator->errors(), 400);
        }

        // Actualizar el proveedor
        $proveedor->update($request->all());
        Log::info('Proveedor actualizado correctamente: ' . $proveedor->id);
        return response()->json(['message' => 'Proveedor actualizado correctamente', $proveedor], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $proveedor = Proveedor::find($id);

        if (!$proveedor) {
            Log::error('Proveedor no encontrado en destroy: ' . $id);
            return response()->json(['error' => 'Proveedor no encontrado'], 404);
        }

        // Verificar permisos de eliminación
        if ($this->verificarRol('Empleado') || ($this->verificarRol('Owner') && $proveedor->created_by !== Auth::user()->id)) {
            Log::warning('Permiso denegado en destroy para el usuario: ' . Auth::user()->id);
            return response()->json(['error' => 'No tienes permiso para eliminar este proveedor'], 403);
        }

        $proveedor->delete();
        Log::info('Proveedor eliminado correctamente: ' . $proveedor->id);
        return response()->json(['message' => 'Proveedor eliminado correctamente'], 200);
    }

    /**
     * Paginación de proveedores según el rol del usuario
     */
    public function paginatedIndex(Request $request)
    {
        $user = Auth::user();

        // Validar la solicitud
        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|string|max:255',
            'direccion' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|max:255',
            'telefono' => 'sometimes|string|max:255',
            'Cuidad' => 'sometimes|string|max:255',
            'Activo' => 'sometimes|boolean',
            'deleted_at' => 'sometimes|boolean',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            Log::error('Validación fallida en paginatedIndex: ' . json_encode($validator->errors()));
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Obtener los datos validados
        $validatedData = $validator->validated();

        // Construir la consulta con los filtros opcionales
        $query = Proveedor::query();

        if (isset($validatedData['nombre'])) {
            $query->where('nombre', 'like', '%' . $validatedData['nombre'] . '%');
        }

        if (isset($validatedData['direccion'])) {
            $query->where('direccion', 'like', '%' . $validatedData['direccion'] . '%');
        }

        if (isset($validatedData['email'])) {
            $query->where('email', 'like', '%' . $validatedData['email'] . '%');
        }

        if (isset($validatedData['telefono'])) {
            $query->where('telefono', 'like', '%' . $validatedData['telefono'] . '%');
        }

        if (isset($validatedData['Cuidad'])) {
            $query->where('Cuidad', 'like', '%' . $validatedData['Cuidad'] . '%');
        }

        if (isset($validatedData['Activo'])) {
            $query->where('Activo', $validatedData['Activo']);
        }

        // Si el usuario es empleado, filtrar por sitio_id
        if ($this->verificarRol('Empleado')) {
            $query->where('sitio_id', $user->sitio_id);
        }

        // Si el usuario es owner, filtrar por created_by
        if ($this->verificarRol('Owner')) {
            $query->where('created_by', $user->id);
        }

        // Filtrar por deleted_at
        if (isset($validatedData['deleted_at'])) {
            if ($validatedData['deleted_at']) {
                $query->onlyTrashed(); // Solo proveedores eliminados
            } else {
                $query->withTrashed(); // Todos los proveedores, incluidos los eliminados
            }
        }

        // Obtener el número de resultados por página, por defecto 15
        $perPage = $validatedData['per_page'] ?? 15;

        // Obtener los resultados paginados
        $proveedores = $query->paginate($perPage);

        Log::info('Proveedores paginados obtenidos correctamente para el usuario: ' . $user->id);
        return response()->json($proveedores);
    }

}