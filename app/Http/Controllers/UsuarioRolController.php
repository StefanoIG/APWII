<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Rol;
use Illuminate\Http\Request;

class UsuarioRolController extends Controller
{
    // Asignar un rol a un usuario
    public function store(Request $request)
    {
        $request->validate([
            'usuario_id' => 'required|exists:usuarios,id',
            'rol_id' => 'required|exists:roles,id',
        ]);

        $usuario = Usuario::findOrFail($request->usuario_id);
        $rol = Rol::findOrFail($request->rol_id);

        $usuario->roles()->attach($rol);
        return response()->json(['message' => 'Rol asignado al usuario correctamente']);
    }

    // Remover un rol de un usuario
    public function destroy(Request $request)
    {
        $request->validate([
            'usuario_id' => 'required|exists:usuarios,id',
            'rol_id' => 'required|exists:roles,id',
        ]);

        $usuario = Usuario::findOrFail($request->usuario_id);
        $rol = Rol::findOrFail($request->rol_id);

        $usuario->roles()->detach($rol);
        return response()->json(['message' => 'Rol removido del usuario correctamente']);
    }

    // Obtener los roles de un usuario
    public function show($usuarioId)
    {
        $usuario = Usuario::findOrFail($usuarioId);
        $roles = $usuario->roles;
        return response()->json($roles);
    }
}
