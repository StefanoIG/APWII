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
        $user = auth()->user();  // Esto asegura que el token sea válido

        $etiquetas = Etiqueta::all();
        return response()->json($etiquetas, 200);
    }

    /**
     * Crear una nueva etiqueta.
     */
    public function store(Request $request)
    {
        $user = auth()->user();  // Verificación del usuario autenticado

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
        $user = auth()->user();  // Verificación del usuario autenticado

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
        $user = auth()->user();  // Verificación del usuario autenticado

        $etiqueta = Etiqueta::findOrFail($id);
        return response()->json($etiqueta, 200);
    }
}
