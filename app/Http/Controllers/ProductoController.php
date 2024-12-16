<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Picqer\Barcode\BarcodeGeneratorPNG;

class ProductoController extends Controller
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
     * Mostrar una lista de productos.
     */
    public function index(Request $request)
    {
        $this->setTenantConnection($request);

        $user = Auth::user();

        if ($this->verificarPermiso('Puede ver productos')) {
            $productos = Producto::all();
            return response()->json($productos, 200);
        }

        return response()->json(['error' => 'No tienes permiso para ver productos'], 403);
    }

    /**
     * Crear un producto.
     */
    public function store(Request $request)
    {
        $this->setTenantConnection($request);

        if (!$this->verificarPermiso('Puede crear productos')) {
            return response()->json(['error' => 'No tienes permiso para crear productos'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nombre_producto' => 'required|string|max:255',
            'tipo_producto' => 'required|string|max:255',
            'descripcion_producto' => 'required|string|max:255',
            'precio' => 'required|numeric',
            'id_etiqueta' => 'integer|nullable',
            'isActive' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();

        try {
            // Generar el código de barras antes de crear el producto
            $codigoBarra = $this->generarCodigoDeBarras();
            //dd($codigoBarra);

            // Crear el producto con el código de barras incluido
            $producto = Producto::create([
                'nombre_producto' => $request->nombre_producto,
                'tipo_producto' => $request->tipo_producto,
                'descripcion_producto' => $request->descripcion_producto,
                'precio' => $request->precio,
                'id_etiqueta' => $request->id_etiqueta,
                'isActive' => $request->isActive,
                'codigo_barra' => $codigoBarra,
            ]);

            DB::commit();
            return response()->json(['message' => 'Producto creado con éxito', 'producto' => $producto], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear producto: ' . $e->getMessage());
            return response()->json(['error' => 'Error al crear producto'], 500);
        }
    }


    /**
     * Mostrar un producto específico.
     */
    public function show(Request $request, $id)
    {
        $this->setTenantConnection($request);

        // if (!$this->verificarPermiso('Puede ver productos')) {
        //     return response()->json(['error' => 'No tienes permiso para ver productos'], 403);
        // }

        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }

        return response()->json($producto, 200);
    }

    /**
     * Actualizar un producto.
     */
    public function update(Request $request, $id)
    {
        $this->setTenantConnection($request);

        // if (!$this->verificarPermiso('Puede editar productos')) {
        //     return response()->json(['error' => 'No tienes permiso para editar productos'], 403);
        // }

        $validator = Validator::make($request->all(), [
            'nombre_producto' => 'required|string|max:255',
            'tipo_producto' => 'required|string|max:255',
            'descripcion_producto' => 'required|string|max:255',
            'precio' => 'required|numeric',
            'id_etiqueta' => 'integer|nullable',
            'isActive' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $producto = Producto::find($id);

            if (!$producto) {
                return response()->json(['error' => 'Producto no encontrado'], 404);
            }

            $producto->update($request->all());
            return response()->json(['message' => 'Producto actualizado con éxito'], 200);
        } catch (\Exception $e) {
            Log::error('Error al actualizar producto: ' . $e->getMessage());
            return response()->json(['error' => 'Error al actualizar producto'], 500);
        }
    }

    /**
     * Eliminar un producto.
     */
    public function destroy(Request $request, $id)
    {
        $this->setTenantConnection($request);

        if (!$this->verificarPermiso('Puede eliminar productos')) {
            return response()->json(['error' => 'No tienes permiso para eliminar productos'], 403);
        }

        try {
            $producto = Producto::find($id);

            if (!$producto) {
                return response()->json(['error' => 'Producto no encontrado'], 404);
            }

            $producto->delete();
            return response()->json(['message' => 'Producto eliminado con éxito'], 200);
        } catch (\Exception $e) {
            Log::error('Error al eliminar producto: ' . $e->getMessage());
            return response()->json(['error' => 'Error al eliminar producto'], 500);
        }
    }

    public function verCodigoDeBarras(Request $request, $id)
    {
        $this->setTenantConnection($request);

        // Buscar el producto por ID en la base de datos del tenant (el middleware ya establece la conexión)
        $producto = Producto::findOrFail($id);

        // Verificar si el producto tiene un código de barras
        if (!$producto->codigo_barra) {
            return response()->json(['error' => 'El producto no tiene un código de barras asignado.'], 404);
        }

        // Generar la imagen del código de barras a partir del código almacenado
        $generatorPNG = new BarcodeGeneratorPNG();
        $image = $generatorPNG->getBarcode($producto->codigo_barra, $generatorPNG::TYPE_CODE_128);

        // Devolver la imagen como respuesta
        return response($image)->header('Content-type', 'image/png');
    }

    private function generarCodigoDeBarras()
    {
        // Generar un número aleatorio de 8 dígitos
        $codigoBarra = str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);

        return $codigoBarra;
    }
}
