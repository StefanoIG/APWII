<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MetodoPagoController extends Controller
{
    public function index()
    {
        $metodosPago = MetodoPago::all();
        return response()->json($metodosPago);
    }

    public function store(Request $request)
    {
        // Validar la solicitud
        $validator = Validator::make($request->all(), [
            'nombre_metodo' => 'required|string|max:255',
            // otros campos y sus validaciones
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Crear el método de pago
        $metodoPago = MetodoPago::create($validator->validated());
        return response()->json($metodoPago, 201);
    }

    public function show($id)
    {
        $metodoPago = MetodoPago::findOrFail($id);
        return response()->json($metodoPago);
    }

    public function update(Request $request, $id)
    {
        $metodoPago = MetodoPago::findOrFail($id);
        $metodoPago->update($request->all());
        return response()->json($metodoPago);
    }

    public function destroy($id)
    {
        MetodoPago::destroy($id);
        return response()->json(null, 204);
    }

    public function paginatedIndex(Request $request)
    {
        // Validar la solicitud
        $validator = Validator::make($request->all(), [
            'nombre_metodo' => 'sometimes|string|max:255',
            'descripcion' => 'sometimes|string|max:255',
            'deleted_at' => 'sometimes|boolean', // Use boolean to filter active/inactive
            'per_page' => 'sometimes|integer|min:1|max:100', // Limitar el número de resultados por página
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Obtener los datos validados
        $validatedData = $validator->validated();

        // Construir la consulta con los filtros opcionales
        $query = MetodoPago::query();

        if (isset($validatedData['nombre_metodo'])) {
            $query->where('nombre_metodo', 'like', '%' . $validatedData['nombre_metodo'] . '%');
        }

        if (isset($validatedData['descripcion'])) {
            $query->where('descripcion', 'like', '%' . $validatedData['descripcion'] . '%');
        }

        // Filtrar por deleted_at
        if (isset($validatedData['deleted_at'])) {
            if ($validatedData['deleted_at']) {
                $query->onlyTrashed(); // Solo métodos de pago eliminados
            } else {
                $query->withTrashed(); // Todos los métodos de pago, incluidos los eliminados
            }
        }

        // Obtener el número de resultados por página, por defecto 15
        $perPage = $validatedData['per_page'] ?? 15;

        // Obtener los resultados paginados
        $metodosPago = $query->paginate($perPage);

        return response()->json($metodosPago);
    }
}