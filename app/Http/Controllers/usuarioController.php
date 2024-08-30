<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
class UsuarioController extends Controller
{
    /**
     * Mostrar la lista de usuarios.
     */
    public function index()
    {
        $user = Auth::user(); // Obtiene el usuario autenticado

        if ($user->rol === 'admin') {
            // Admin puede ver todos los usuarios
            $usuarios = Usuario::all();
        } else {
            // Empleado solo puede ver su propia información
            $usuarios = Usuario::where('id', $user->id)->get();
        }

        return response()->json($usuarios);
    }


    /**
     * Crear un nuevo usuario.
     */
    public function register(Request $request)
    {
        // Validación de datos
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'telefono' => 'required|string|max:20',
            'cedula' => 'required|string|max:10|unique:usuarios,cedula',
            'correo_electronico' => 'required|email|unique:usuarios,correo_electronico',
            'password' => 'required|string|min:6',
            'rol' => 'in:empleado,admin,cliente',  // Validación del rol
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Creación del usuario
        $usuario = Usuario::create([
            'nombre' => $request->nombre,
            'apellido' => $request->apellido,
            'telefono' => $request->telefono,
            'cedula' => $request->cedula,
            'correo_electronico' => $request->correo_electronico,
            'password' => $request->password,
            'rol' => $request->rol ?? 'empleado',  // Rol por defecto
        ]);

        return response()->json(['message' => 'Usuario creado exitosamente', 'usuario' => $usuario], 201);
    }

    /**
     * Editar la información del usuario.
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user(); // Obtiene el usuario autenticado
        $usuario = Usuario::findOrFail($id);

        // Verificar permisos
        if ($user->rol === 'empleado' && $user->id !== $usuario->id) {
            return response()->json(['error' => 'No tienes permisos para actualizar esta información'], 403);
        }

        // Validación de datos
        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|required|string',
            'apellido' => 'sometimes|required|string',
            'telefono' => 'sometimes|required|string',
            'cedula' => 'sometimes|required|string|max:10|unique:usuarios,cedula,' . $usuario->id,
            'correo_electronico' => 'sometimes|required|email|unique:usuarios,correo_electronico,' . $usuario->id,
            'contrasena' => 'sometimes|required|string|min:6',
            'rol' => 'sometimes|in:empleado,admin,cliente',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Actualización del usuario
        if ($user->rol === 'empleado') {
            // Solo permitir actualización de ciertos campos para empleados
            $requestData = $request->only(['nombre', 'apellido', 'telefono', 'cedula', 'correo_electronico']);
            if ($request->has('contrasena')) {
                $requestData['contrasena'] = bcrypt($request->contrasena);
            }
            $usuario->update($requestData);
        } else {
            // Admin puede actualizar todos los campos
            if ($request->has('contrasena')) {
                $request->merge(['contrasena' => bcrypt($request->contrasena)]);
            }
            $usuario->update($request->all());
        }

        return response()->json(['message' => 'Usuario actualizado exitosamente', 'usuario' => $usuario], 200);
    }
}
