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
        $validatedData = $request->validate([
            'id_producto' => 'required|exists:producto,id_producto',
            'id_proveedor' => 'required|exists:proveedor,id_proveedor',
            'fecha_fabricacion' => 'nullable|date',
            'fecha_caducidad' => 'nullable|date',
            'cantidad' => 'required|integer',
            'espirable' => 'required|boolean',
            'isActive' => 'required|boolean',
            'id_sitio' => 'required|exists:sitio,id_sitio'
        ]);
    
        // Uso de transacciones para asegurar consistencia
        DB::beginTransaction();
        try {
            // Crear un nuevo lote y asignar manualmente las claves foráneas
            $lote = new Lote();
            $lote->id_producto = $validatedData['id_producto'];
            $lote->id_proveedor = $validatedData['id_proveedor'];
            $lote->fecha_fabricacion = $validatedData['fecha_fabricacion'];
            $lote->fecha_caducidad = $validatedData['fecha_caducidad'];
            $lote->cantidad = $validatedData['cantidad'];
            $lote->espirable = $validatedData['espirable'];
            $lote->isActive = $validatedData['isActive'];
            $lote->id_sitio=$validatedData['id_sitio'];
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
}    