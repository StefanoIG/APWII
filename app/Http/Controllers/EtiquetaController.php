<?php

namespace App\Http\Controllers;

use App\Models\Etiqueta;
use App\Models\Producto;
use App\Models\Lote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class EtiquetaController extends Controller
{
    /**
     * Establecer la conexión al tenant correspondiente usando el nombre de la base de datos.
     */
    protected function setTenantConnection(Request $request)
    {
        $tenantDatabase = $request->header('X-Tenant');

        if (!$tenantDatabase) {
            abort(400, 'El encabezado X-Tenant es obligatorio.');
        }

        config(['database.connections.tenant' => [
            'driver' => 'sqlite',
            'database' => database_path('tenants/' . $tenantDatabase),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);

        DB::purge('tenant');
        DB::reconnect('tenant');
        DB::setDefaultConnection('tenant');
    }

    /**
     * Verificar si el usuario autenticado tiene un permiso específico.
     */
    private function verificarPermiso($permisoNombre)
    {
        $user = Auth::user();

        if (!$user) {
            Log::error('Usuario no autenticado al verificar permiso: ' . $permisoNombre);
            return false;
        }

        $user->load('roles.permisos');

        foreach ($user->roles as $rol) {
            if ($rol->permisos->contains('nombre', $permisoNombre)) {
                return true;
            }
        }

        Log::warning('Permiso no encontrado: ' . $permisoNombre . ' para el usuario: ' . $user->id);
        return false;
    }

    /**
     * Obtener todas las etiquetas.
     */
    public function index(Request $request)
    {
        $this->setTenantConnection($request);

        $etiquetas = Etiqueta::all();
        return response()->json($etiquetas, 200);        
    }

    /**
     * Crear una nueva etiqueta.
     */
    public function store(Request $request)
    {
        $this->setTenantConnection($request);

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
        $this->setTenantConnection($request);

        // if (!$this->verificarPermiso('Puede actualizar etiquetas')) {
        //     return response()->json(['error' => 'No tienes permiso para actualizar etiquetas'], 403);
        // }

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
    public function show(Request $request, $id)
    {
        $this->setTenantConnection($request);

        $etiqueta = Etiqueta::findOrFail($id);
        return response()->json($etiqueta, 200);
    }

    /**
     * Eliminar una etiqueta existente.
     */
    public function destroy(Request $request, $id)
    {
        $this->setTenantConnection($request);

        // if (!$this->verificarPermiso('Puede borrar etiquetas')) {
        //     return response()->json(['error' => 'No tienes permiso para eliminar etiquetas'], 403);
        // }

        $etiqueta = Etiqueta::findOrFail($id);
        $etiqueta->delete();

        return response()->json(['message' => 'Etiqueta eliminada exitosamente'], 200);
    }

    /**
     * Asignar una etiqueta a un producto.
     */
    public function asignarEtiquetaProducto(Request $request)
    {
        $this->setTenantConnection($request);

        $validator = Validator::make($request->all(), [
            'producto_id' => 'required|exists:producto,id_producto',
            'etiqueta_id' => 'required|exists:etiqueta,id_etiqueta'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $producto = Producto::findOrFail($request->input('producto_id'));
        $producto->etiquetas()->attach($request->input('etiqueta_id'));

        return response()->json(['message' => 'Etiqueta asignada al producto exitosamente'], 200);
    }

    /**
     * Asignar una etiqueta a un lote.
     */
    public function asignarEtiquetaLote(Request $request)
    {
        $this->setTenantConnection($request);

        $validator = Validator::make($request->all(), [
            'lote_id' => 'required|exists:lote,id_lote',
            'etiqueta_id' => 'required|exists:etiqueta,id_etiqueta'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $lote = Lote::findOrFail($request->input('lote_id'));
        $lote->etiquetas()->attach($request->input('etiqueta_id'));

        return response()->json(['message' => 'Etiqueta asignada al lote exitosamente'], 200);
    }
}
