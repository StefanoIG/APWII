<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sitio;

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

    // Crear un nuevo sitio
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nombre_sitio' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'ciudad' => 'required|string|max:255',
            'pais' => 'required|string|max:255',
        ]);

        $sitio = Sitio::create($validatedData);

        return response()->json(['message' => 'Sitio creado con éxito', 'sitio' => $sitio], 201);
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
}
