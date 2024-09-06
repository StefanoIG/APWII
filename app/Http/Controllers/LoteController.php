<?php

namespace App\Http\Controllers;

use App\Models\Lote;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        // Validar la solicitud
        $request->validate([
            'producto_id' => 'required|exists:producto,id_producto',
            'id_proveedor' => 'required|exists:proveedor,id_proveedor',
            'fecha_fabricacion' => 'nullable|date',
            'fecha_caducidad' => 'nullable|date',
            'cantidad' => 'required|integer',
            'espirable' => 'required|boolean',
            'isActive' => 'required|boolean'
        ]);
    
        // Uso de transacciones para asegurar consistencia
        DB::beginTransaction();
        try {
            // Crear un nuevo lote
            $lote = Lote::create($request->all());
    
            // Generar código de barras aleatorio de longitud fija
            $codigoBarra = $this->generarCodigoDeBarras();
    
            // Guardar el código de barras en el lote
            $lote->codigo_barra = $codigoBarra;
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
    public function verCodigoDeBarras($id)
    {
        // Buscar el lote por ID
        $lote = Lote::findOrFail($id);

        // Verificar si el lote tiene un código de barras
        if (!$lote->codigo_barra) {
            return response()->json(['error' => 'El lote no tiene un código de barras asignado.'], 404);
        }

        // Generar la imagen del código de barras a partir del código de la base de datos
        $generatorPNG = new BarcodeGeneratorPNG();
        $image = $generatorPNG->getBarcode($lote->codigo_barra, $generatorPNG::TYPE_CODE_128);

        // Devolver la imagen como respuesta
        return response($image)->header('Content-type', 'image/png');
    }
}

