<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sitio;
use Illuminate\Support\Facades\Validator;
use App\Models\Lote;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\UsuarioController, verificarRol, verificarPermiso;

class SitioController extends Controller
{
    private function verificarPermiso($permisoNombre)
    {
        $user = Auth::user();

        // Obtener los roles asociados al usuario
        $roles = $user->roles; // Asumiendo que el modelo Usuario tiene una relación con roles

        // Iterar sobre cada rol del usuario
        foreach ($roles as $rol) {
            // Verificar si el rol tiene el permiso requerido
            if ($rol->permisos()->where('nombre', $permisoNombre)->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica si el usuario autenticado tiene un rol específico.
     *
     * @param string $rolNombre
     * @return bool
     */
    private function verificarRol($rolNombre)
    {
        $user = Auth::user();

        // Obtener los roles asociados al usuario
        $roles = $user->roles; // Asumiendo que el modelo Usuario tiene una relación con roles

        // Verificar si alguno de los roles coincide con el nombre del rol requerido
        foreach ($roles as $rol) {
            if ($rol->nombre === $rolNombre) {
                return true;
            }
        }

        return false;
    }



    // Mostrar todos los sitios según el rol del usuario
    public function index()
    {
        $user = Auth::user();

        // Si el usuario es un empleado, solo puede ver los sitios donde su sitio_id coincide
        if ($this->verificarRol('Empleado')) {
            $sitios = Sitio::where('sitio_id', $user->sitio_id)->get();
            return response()->json($sitios);
        }

        // Si el usuario es owner, solo puede ver los sitios que él haya creado
        if ($this->verificarRol('Owner')) {
            $sitios = Sitio::where('created_by', $user->id)->get();
            return response()->json($sitios);
        }

        // Si el usuario es admin, puede ver todos los sitios
        if ($this->verificarRol('Admin')) {
            $sitios = Sitio::all();
            return response()->json($sitios);
        }

        // Si no tiene ningún rol relevante, devolver un error
        return response()->json(['error' => 'No tienes permiso para ver esta información'], 403);
    }




    // Mostrar un sitio específico por ID
    public function show($id)
    {
        $sitio = Sitio::find($id);

        if (!$sitio) {
            return response()->json(['error' => 'Sitio no encontrado'], 404);
        }

        if (!$this->verificarPermiso('Puede ver informacion de todos los usuarios') && !$this->verificarPermiso('Puede ver informacion usuarios de un solo sitio')) {
            return response()->json(['error' => 'No tienes permiso para ver esta información'], 403);
        }

        return response()->json($sitio);
    }

    public function store(Request $request)
    {
        if (!$this->verificarPermiso('Puede crear sitios')) {
            return response()->json(['error' => 'No tienes permiso para crear sitios'], 403);
        }

        // Validar la solicitud
        $validator = Validator::make($request->all(), [
            'nombre_sitio' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'ciudad' => 'required|string|max:255',
            'pais' => 'required|string|max:255',
            'id' => 'nullable|exists:usuarios,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Crear el sitio
        $sitio = Sitio::create([
            'nombre_sitio' => $request->nombre_sitio,
            'direccion' => $request->direccion,
            'ciudad' => $request->ciudad,
            'pais' => $request->pais,
            'created_by' => $request->id,
        ]);

        return response()->json(['message' => 'Sitio creado con éxito', 'sitio' => $sitio], 201);
    }

    // Actualizar un sitio
    public function update(Request $request, $id)
    {
        $sitio = Sitio::find($id);

        if (!$sitio) {
            return response()->json(['error' => 'Sitio no encontrado'], 404);
        }

        if (!$this->verificarPermiso('Puede actualizar sitios')) {
            return response()->json(['error' => 'No tienes permiso para actualizar sitios'], 403);
        }

        // Validar los datos actualizados
        $validatedData = $request->validate([
            'nombre_sitio' => 'sometimes|required|string|max:255',
            'direccion' => 'sometimes|required|string|max:255',
            'ciudad' => 'sometimes|required|string|max:255',
            'pais' => 'sometimes|required|string|max:255',
        ]);

        // Actualizar el sitio
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

        if (!$this->verificarPermiso('Puede borrar sitios')) {
            return response()->json(['error' => 'No tienes permiso para eliminar sitios'], 403);
        }

        // Realiza un soft delete
        $sitio->delete();

        return response()->json(['message' => 'Sitio eliminado con éxito'], 200);
    }

    // Index paginado
    // Paginación de sitios según el rol del usuario
    public function paginatedIndex(Request $request)
    {
        $user = Auth::user();

        // Validar la solicitud
        $validator = Validator::make($request->all(), [
            'nombre_sitio' => 'sometimes|string|max:255',
            'direccion' => 'sometimes|string|max:255',
            'ciudad' => 'sometimes|string|max:255',
            'pais' => 'sometimes|string|max:255',
            'deleted_at' => 'sometimes|boolean',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Obtener los datos validados
        $validatedData = $validator->validated();

        // Construir la consulta con los filtros opcionales
        $query = Sitio::query();

        if (isset($validatedData['nombre_sitio'])) {
            $query->where('nombre_sitio', 'like', '%' . $validatedData['nombre_sitio'] . '%');
        }

        if (isset($validatedData['direccion'])) {
            $query->where('direccion', 'like', '%' . $validatedData['direccion'] . '%');
        }

        if (isset($validatedData['ciudad'])) {
            $query->where('ciudad', 'like', '%' . $validatedData['ciudad'] . '%');
        }

        if (isset($validatedData['pais'])) {
            $query->where('pais', 'like', '%' . $validatedData['pais'] . '%');
        }

        // Filtrar por deleted_at
        if (isset($validatedData['deleted_at'])) {
            if ($validatedData['deleted_at']) {
                $query->onlyTrashed(); // Solo sitios eliminados
            } else {
                $query->withTrashed(); // Todos los sitios, incluidos los eliminados
            }
        }

        // Si el usuario es empleado, filtrar por sitio_id
        if ($this->verificarRol('Empleado')) {
            $query->where('sitio_id', $user->sitio_id);
        }

        // Si el usuario es owner, filtrar por created_by
        if ($this->verificarRol('Owner')) {
            $query->where('created_by', $user->id);
        }

        // Obtener el número de resultados por página, por defecto 15
        $perPage = $validatedData['per_page'] ?? 15;

        // Obtener los resultados paginados
        $sitios = $query->paginate($perPage);

        return response()->json($sitios);
    }
}
