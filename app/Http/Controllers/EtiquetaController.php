<?php
namespace App\Http\Controllers;

use App\Models\Etiqueta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EtiquetaController extends Controller
{
   
    /**
     * Obtener todas las etiquetas.
     */
    public function index()
    {

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
       

        $etiqueta = Etiqueta::findOrFail($id);
        $etiqueta->delete();

        return response()->json(['message' => 'Etiqueta eliminada exitosamente'], 200);
    }

    public function paginatedIndex(Request $request)
    {
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
}
