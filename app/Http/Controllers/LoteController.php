<?php

namespace App\Http\Controllers;

use App\Models\Lote;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LoteController extends Controller
{
    // Obtener todos los lotes
    public function index()
    {
        $lotes = Lote::all();
        return response()->json($lotes);
    }

    // Obtener un lote específico por ID
    public function show($id)
    {
        $lote = Lote::find($id);

        if (!$lote) {
            return response()->json(['message' => 'Lote no encontrado'], 404);
        }

        return response()->json($lote);
    }


    // Crear un nuevo lote
    public function store(Request $request)
    {
        // Validar la solicitud con una regla personalizada
        $validator = Validator::make($request->all(), [
            'id_producto' => 'required|exists:producto,id_producto',
            'id_proveedor' => 'required|exists:proveedor,id_proveedor',
            'fecha_fabricacion' => 'nullable|date',
            'fecha_caducidad' => 'nullable|date',
            'cantidad' => 'required|integer',
            'expirable' => 'required|boolean',
            'isActive' => 'required|boolean',
            'id_sitio' => 'required|exists:sitio,id_sitio'
        ]);

        // Agregar una regla personalizada para validar fecha_caducidad
        $validator->sometimes('fecha_caducidad', 'required|date', function ($input) {
            return $input->expirable == true;
        });

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Uso de transacciones para asegurar consistencia
        DB::beginTransaction();
        try {
            // Crear un nuevo lote y asignar manualmente las claves foráneas
            $validatedData = $validator->validated();
            $lote = new Lote();
            $lote->id_producto = $validatedData['id_producto'];
            $lote->id_proveedor = $validatedData['id_proveedor'];
            $lote->fecha_fabricacion = $validatedData['fecha_fabricacion'];
            $lote->cantidad = $validatedData['cantidad'];
            $lote->expirable = $validatedData['expirable'];
            $lote->isActive = $validatedData['isActive'];
            $lote->id_sitio = $validatedData['id_sitio'];

            // Asignar fecha_caducidad solo si expirable es true
            if ($validatedData['expirable']) {
                $lote->fecha_caducidad = $validatedData['fecha_caducidad'];
            }

            // Generar código de barras aleatorio de longitud fija
            $codigoBarra = $this->generarCodigoDeBarras();
            $lote->codigo_lote = $codigoBarra;

            // Guardar el lote
            $lote->save();

            DB::commit();
            return response()->json($lote, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear lote: ' . $e->getMessage());
            return response()->json(['error' => 'Error al crear el lote'], 500);
        }
    }

    // Generar un código de barras de longitud fija
    private function generarCodigoDeBarras()
    {
        // Generar un código de barra de 8 caracteres numéricos
        return str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);
    }

    // Generar y mostrar la imagen del código de barras
    public function verCodigoDeBarras($id_lote)
    {

        // Buscar el lote por id_lote
        $lote = Lote::findOrFail($id_lote);

        // Verificar si el lote tiene un código de barras
        if (!$lote->codigo_lote) {
            return response()->json(['error' => 'El lote no tiene un código de barras asignado.'], 404);
        }

        // Generar la imagen del código de barras a partir del código de la base de datos
        $generatorPNG = new BarcodeGeneratorPNG();
        $image = $generatorPNG->getBarcode($lote->codigo_lote, $generatorPNG::TYPE_CODE_128);

        // Devolver la imagen como respuesta
        return response($image)->header('Content-type', 'image/png');
    }

    public function showByCodigoLote(Request $request)
    {
        // Validar que el código de lote fue enviado en la solicitud
        $validator = Validator::make($request->all(), [
            'codigo_lote' => 'required|string|exists:lote,codigo_lote',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Buscar el lote por código de lote con la información del producto asociado
        $lote = Lote::with('producto')->where('codigo_lote', $request->codigo_lote)->first();

        if (!$lote) {
            return response()->json(['message' => 'Lote no encontrado'], 404);
        }

        return response()->json([
            'lote' => $lote,
            'producto' => $lote->producto
        ]);
    }

    // Actualizar un lote existente
    public function update(Request $request, $id)
    {
        // Validar la solicitud con una regla personalizada
        $validator = Validator::make($request->all(), [
            'id_producto' => 'sometimes|exists:producto,id_producto',
            'id_proveedor' => 'sometimes|exists:proveedor,id_proveedor',
            'fecha_fabricacion' => 'sometimes|date',
            'fecha_caducidad' => 'sometimes|date',
            'cantidad' => 'sometimes|integer',
            'expirable' => 'sometimes|boolean',
            'isActive' => 'sometimes|boolean',
            'id_sitio' => 'sometimes|exists:sitio,id_sitio'
        ]);

        // Agregar una regla personalizada para validar fecha_caducidad
        $validator->sometimes('fecha_caducidad', 'required|date', function ($input) {
            return $input->expirable == true;
        });

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Uso de transacciones para asegurar consistencia
        DB::beginTransaction();
        try {
            // Buscar el lote por ID
            $lote = Lote::findOrFail($id);

            // Actualizar el lote con los datos validados
            $validatedData = $validator->validated();
            $lote->fill($validatedData);

            // Asignar fecha_caducidad solo si expirable es true
            if ($lote->expirable) {
                $lote->fecha_caducidad = $validatedData['fecha_caducidad'];
            }

            // Guardar el lote
            $lote->save();

            DB::commit();
            return response()->json($lote, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar lote: ' . $e->getMessage());
            return response()->json(['error' => 'Error al actualizar el lote'], 500);
        }
    }


    // Eliminar un lote existente
    public function destroy($id)
    {
        // Buscar el lote por ID
        $lote = Lote::find($id);

        if (!$lote) {
            return response()->json(['message' => 'Lote no encontrado'], 404);
        }

        // Eliminar el lote
        $lote->delete();

        return response()->json(['message' => 'Lote eliminado correctamente'], 200);
    }
}
