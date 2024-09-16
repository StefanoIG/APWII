<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Proveedor;
//validator
use Illuminate\Support\Facades\Validator;


class ProveedorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //Retorna todos los proveedores
        return Proveedor::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //Validacion de los datos
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'email' => 'required|string|max:255',
            'telefono' => 'required|string|max:255',
            'Cuidad' => 'required|string|max:255',
            'Activo' => 'required|boolean',
            'isActive' => 'required|boolean',
        ]);

        //Si la validacion falla
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        //Si la validacion es correcta
        $proveedor = Proveedor::create($request->all());
        return response()->json(['message' => 'Proveedor Creado correctamente', $proveedor], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //Mostrar un proveedor en base a la id que se envia
        return Proveedor::find($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //Validacion de los datos
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'email' => 'required|string|max:255',
            'telefono' => 'required|string|max:255',
            'Cuidad' => 'required|string|max:255',
            'Activo' => 'required|boolean',
        ]);

        //Si la validacion falla
        if ($validator->fails()) {
            //log con errores

            return response()->json($validator->errors(), 400);
        }

        //Si la validacion es correcta
        $proveedor = Proveedor::find($id);
        $proveedor->update($request->all());
        return response()->json(['message' => 'Proveedor actualizado correctamente', $proveedor], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //Eliminar un proveedor en base a la id que se envia
        $proveedor = Proveedor::find($id);
        $proveedor->delete();
        return response()->json(['message' => 'Proveedor eliminado correctamente'], 200);
    }

    public function paginatedIndex(Request $request)
    {
        // Validar la solicitud
        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|string|max:255',
            'direccion' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|max:255',
            'telefono' => 'sometimes|string|max:255',
            'Cuidad' => 'sometimes|string|max:255',
            'Activo' => 'sometimes|boolean',
            'deleted_at' => 'sometimes|boolean', // Use boolean to filter active/inactive
            'per_page' => 'sometimes|integer|min:1|max:100', // Limitar el número de resultados por página
        ]);

        if ($validator->fails()) {
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

        return response()->json($proveedores);
    }
}
