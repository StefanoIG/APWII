<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\Request;
//validator
use Illuminate\Support\Facades\Validator;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;



class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        return Producto::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validar la solicitud
        $validator = Validator::make($request->all(), [
            'nombre_producto' => 'required|string|max:255',
            'tipo_producto' => 'required|string|max:255',
            'descripcion_producto' => 'required|string|max:255',
            'precio' => 'required|numeric',
            'id_etiqueta' => 'integer|nullable',
            'isActive' => 'required|boolean',
        ]);

        // Si la validación falla
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        DB::beginTransaction();
        try {
            // Crear el producto
            $producto = Producto::create($request->all());

            // Generar código de barras de longitud fija
            $codigoBarra = $this->generarCodigoDeBarras();

            // Guardar código de barras en la base de datos
            $producto->codigo_barra = $codigoBarra;
            $producto->save();

            DB::commit();
            return response()->json($producto, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear producto: ' . $e->getMessage());
            return response()->json(['error' => 'Error al crear el producto'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return Producto::find($id);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Validar la solicitud
        $validator = Validator::make($request->all(), [
            'nombre_producto' => 'sometimes|required|string|max:255',
            'tipo_producto' => 'sometimes|required|string|max:255',
            'descripcion_producto' => 'sometimes|required|string|max:255',
            'precio' => 'sometimes|required|numeric',
            'id_etiqueta' => 'integer|nullable',
            'isActive' => 'sometimes|required|boolean',
        ]);

        // Si la validación falla
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        DB::beginTransaction();
        try {
            // Buscar el producto por ID
            $producto = Producto::findOrFail($id);

            // Actualizar el producto
            $producto->update($request->all());

            DB::commit();
            return response()->json($producto, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar producto: ' . $e->getMessage());
            return response()->json(['error' => 'Error al actualizar el producto'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Buscar el producto por ID
        $producto = Producto::find($id);

        // Si el producto no existe
        if (!$producto) {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }

        // Eliminar el producto
        $producto->delete();

        return response()->json(['message' => 'Producto eliminado correctamente'], 200);
    }

    public function verCodigoDeBarras($id)
    {
        // Buscar el producto por ID
        $producto = Producto::findOrFail($id);

        // Verificar si el producto tiene un código de barras
        if (!$producto->codigo_barra) {
            return response()->json(['error' => 'El producto no tiene un código de barras asignado.'], 404);
        }

        // Generar la imagen del código de barras a partir del código de la base de datos
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

    public function paginatedIndex(Request $request)
{
    // Validar la solicitud
    $validator = Validator::make($request->all(), [
        'nombre_producto' => 'sometimes|string|max:255',
        'tipo_producto' => 'sometimes|string|max:255',
        'descripcion_producto' => 'sometimes|string|max:255',
        'precio' => 'sometimes|numeric',
        'id_etiqueta' => 'sometimes|integer|nullable',
        'deleted_at' => 'sometimes|boolean', // Use boolean to filter active/inactive
        'per_page' => 'sometimes|integer|min:1|max:100', // Limitar el número de resultados por página
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Obtener los datos validados
    $validatedData = $validator->validated();

    // Construir la consulta con los filtros opcionales
    $query = Producto::with('etiquetas'); // Incluye las etiquetas relacionadas

    if (isset($validatedData['nombre_producto'])) {
        $query->where('nombre_producto', 'like', '%' . $validatedData['nombre_producto'] . '%');
    }

    if (isset($validatedData['tipo_producto'])) {
        $query->where('tipo_producto', 'like', '%' . $validatedData['tipo_producto'] . '%');
    }

    if (isset($validatedData['descripcion_producto'])) {
        $query->where('descripcion_producto', 'like', '%' . $validatedData['descripcion_producto'] . '%');
    }

    if (isset($validatedData['precio'])) {
        $query->where('precio', $validatedData['precio']);
    }

    if (isset($validatedData['id_etiqueta'])) {
        // Filtrar productos que tengan una etiqueta específica
        $query->whereHas('etiquetas', function($q) use ($validatedData) {
            $q->where('etiqueta_id', $validatedData['id_etiqueta']);
        });
    }

    // Filtrar por deleted_at
    if (isset($validatedData['deleted_at'])) {
        if ($validatedData['deleted_at']) {
            $query->onlyTrashed(); // Solo productos eliminados
        } else {
            $query->withTrashed(); // Todos los productos, incluidos los eliminados
        }
    }

    // Obtener el número de resultados por página, por defecto 15
    $perPage = $validatedData['per_page'] ?? 15;

    // Obtener los resultados paginados
    $productos = $query->paginate($perPage);

    return response()->json($productos);
}

}
