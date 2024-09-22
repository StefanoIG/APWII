<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Permiso;
use Illuminate\Http\Request;

class RolPermisoController extends Controller
{
    // Asignar un permiso a un rol
    public function store(Request $request)
    {
        $request->validate([
            'rol_id' => 'required|exists:roles,id',
            'permiso_id' => 'required|exists:permisos,id',
        ]);

        $rol = Rol::findOrFail($request->rol_id);
        $permiso = Permiso::findOrFail($request->permiso_id);

        $rol->permisos()->attach($permiso);
        return response()->json(['message' => 'Permiso asignado al rol correctamente']);
    }

    // Remover un permiso de un rol
    public function destroy(Request $request)
    {
        $request->validate([
            'rol_id' => 'required|exists:roles,id',
            'permiso_id' => 'required|exists:permisos,id',
        ]);

        $rol = Rol::findOrFail($request->rol_id);
        $permiso = Permiso::findOrFail($request->permiso_id);

        $rol->permisos()->detach($permiso);
        return response()->json(['message' => 'Permiso removido del rol correctamente']);
    }

    // Obtener los permisos de un rol
    public function show($rolId)
    {
        $rol = Rol::findOrFail($rolId);
        $permisos = $rol->permisos;
        return response()->json($permisos);
    }
}
