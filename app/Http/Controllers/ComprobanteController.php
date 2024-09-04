<?php

namespace App\Http\Controllers;

use App\Models\Comprobante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ComprobanteController extends Controller
{
    // Obtener todos los comprobantes
    public function index()
    {
        try {
            $comprobantes = Comprobante::all();
            return response()->json($comprobantes);
        } catch (\Exception $e) {
            Log::error('Error al obtener comprobantes: ' . $e->getMessage());
            return response()->json(['error' => 'Error en el servidor'], 500);
        }
    }

    // Crear un nuevo comprobante
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'fecha_emision' => 'required|date',
                'bodega' => 'required|string|max:255',
                'usuario_id' => 'required|exists:usuarios,id', // Asegúrate de que la tabla y columna sean correctas
                'id_producto' => 'required|exists:producto,id_producto', // Cambiado a 'producto'
                'cantidad' => 'required|integer',
                'precio_total' => 'required|numeric'
            ]);
    
            $comprobante = Comprobante::create($validatedData);
    
            return response()->json($comprobante, 201);
        } catch (\Exception $e) {
            Log::error('Error al crear comprobante: ' . $e->getMessage());
            return response()->json(['error' => 'Error en el servidor', 'details' => $e->getMessage()], 500);
        }
    }


    // Mostrar un comprobante específico
    public function show($id)
    {
        $comprobante = Comprobante::find($id);

        if(!$comprobante) {
            return response()->json(['error' => 'Comprobante no encontrado'], 404);
        }

        return response()->json($comprobante);
    }

}