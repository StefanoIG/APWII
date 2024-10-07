<?php

namespace App\Http\Controllers;

use App\Models\Permiso;
use Illuminate\Http\Request;

class PermisoController extends Controller
{
    // Obtener todos los permisos
    public function index()
    {
        $permisos = Permiso::all();
        return response()->json($permisos);
    }

    // Crear un nuevo permiso
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|unique:permisos',
            'descripcion' => 'nullable|string',
        ]);

        $permiso = Permiso::create($request->all());
        return response()->json($permiso, 201);
    }

    // Mostrar un permiso específico
    public function show($id)
    {
        $permiso = Permiso::findOrFail($id);
        return response()->json($permiso);
    }

    // Actualizar un permiso
    public function update(Request $request, $id)
    {
        $permiso = Permiso::findOrFail($id);

        $request->validate([
            'nombre' => 'required|unique:permisos,nombre,' . $permiso->id,
            'descripcion' => 'nullable|string',
        ]);

        $permiso->update($request->all());
        return response()->json($permiso);
    }

    // Eliminar un permiso
    public function destroy($id)
    {
        $permiso = Permiso::findOrFail($id);
        $permiso->delete();
        return response()->json(null, 204);
    }
}
