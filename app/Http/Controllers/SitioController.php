<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sitio;
use Illuminate\Support\Facades\Validator;
use App\Models\Lote;
use Illuminate\Support\Facades\Auth;

class SitioController extends Controller
{
    // Mostrar todos los sitios
    public function index()
    {
        $sitios = Sitio::all();
        return response()->json($sitios);
    }

    // Mostrar un sitio específico por ID
    public function show($id)
    {
        $sitio = Sitio::find($id);

        if (!$sitio) {
            return response()->json(['error' => 'Sitio no encontrado'], 404);
        }

        return response()->json($sitio);
    }

    public function store(Request $request)
    {
        // Validar la solicitud
        $validator = Validator::make($request->all(), [
            'nombre_sitio' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'ciudad' => 'required|string|max:255',
            'pais' => 'required|string|max:255',
            'id' => 'nulleable|exists:usuarios,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Crear el sitio con el campo created_by
        $sitio = Sitio::create([
            'nombre_sitio' => $request->nombre_sitio,
            'direccion' => $request->direccion,
            'ciudad' => $request->ciudad,
            'pais' => $request->pais,
            'created_by' => $request->id,
        ]);

        return response()->json(['message' => 'Sitio creado con éxito',$sitio], 201);
    }

    // Actualizar un sitio
    public function update(Request $request, $id)
    {
        $sitio = Sitio::find($id);

        if (!$sitio) {
            return response()->json(['error' => 'Sitio no encontrado'], 404);
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

    // Eliminar un sitio (soft delete)
    public function destroy($id)
    {
        $sitio = Sitio::find($id);

        if (!$sitio) {
            return response()->json(['error' => 'Sitio no encontrado'], 404);
        }

        // Realiza un soft delete
        $sitio->delete();

        return response()->json(['message' => 'Sitio eliminado con éxito'], 200);
    }


    public function paginatedIndex(Request $request)
    {
        // Validar la solicitud
        $validator = Validator::make($request->all(), [
            'deleted_at' => 'sometimes|boolean', // Use boolean to filter active/inactive
            'per_page' => 'sometimes|integer|min:1|max:100', // Limitar el número de resultados por página
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        // Obtener los datos validados
        $validatedData = $validator->validated();
    
        // Construir la consulta con los filtros opcionales
        $query = Lote::query();
    
        // Filtrar por deleted_at
        if (isset($validatedData['deleted_at'])) {
            if ($validatedData['deleted_at']) {
                $query->onlyTrashed(); // Solo lotes eliminados
            } else {
                $query->withTrashed(); // Todos los lotes, incluidos los eliminados
            }
        }
    
        // Obtener el número de resultados por página, por defecto 15
        $perPage = $validatedData['per_page'] ?? 15;
    
        // Obtener los resultados paginados
        $lotes = $query->paginate($perPage);
    
        return response()->json($lotes);
    }
}
