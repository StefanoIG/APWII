<?php

namespace App\Http\Controllers;

use App\Models\Comprobante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf; // Asegúrate de importar DomPDF
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB; // Importar la clase DB
use App\Models\Lote; // Importar el modelo Lote

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
        DB::beginTransaction();
    
        try {
            $validatedData = $request->validate([
                'fecha_emision' => 'required|date',
                'id_lote' => 'required|exists:lote,id_lote',
                'usuario_id' => 'required|exists:usuarios,id',
                'id_producto' => 'required|exists:producto,id_producto',
                'cantidad' => 'required|integer',
                'precio_total' => 'required|numeric'
            ]);
    
            // Verificar que el lote tenga suficientes productos
            $lote = Lote::findOrFail($validatedData['id_lote']);
            if ($lote->cantidad < $validatedData['cantidad']) {
                return response()->json(['error' => 'El lote no tiene suficientes productos.'], 400);
            }
    
            // Crear el comprobante
            $comprobante = Comprobante::create($validatedData);
    
            // Actualizar la cantidad de productos en el lote
            $lote->cantidad -= $validatedData['cantidad'];
            $lote->save();
    
            DB::commit();
    
            return response()->json($comprobante, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear comprobante: ' . $e->getMessage());
            return response()->json(['error' => 'Error en el servidor', 'details' => $e->getMessage()], 500);
        }
    }

    // Método para mostrar el comprobante en PDF
    public function generarPDF($id)
    {
        try {
            // Obtener el comprobante por ID, junto con las relaciones de lote, que a su vez tiene la relación con sitio
            $comprobante = Comprobante::with(['usuario', 'producto', 'lote.sitio'])->findOrFail($id);

            // Cálculo del subtotal
            $subtotal = $comprobante->precio_total;

            // Cálculo del IVA (15%)
            $iva = $subtotal * 0.15;

            // Cálculo del monto final
            $monto_final = $subtotal + $iva;

            // Agregar los cálculos al comprobante para pasarlos a la vista
            $comprobante->subtotal = $subtotal;
            $comprobante->iva = $iva;
            $comprobante->monto_final = $monto_final;

            // Crear el PDF usando la vista comprobante_pdf.blade.php
            $pdf = Pdf::loadView('comprobante_pdf', compact('comprobante'));

            // Descargar el PDF
            return $pdf->download('comprobante_' . $comprobante->id . '.pdf');
        } catch (\Exception $e) {
            Log::error('Error al generar PDF: ' . $e->getMessage());
            return response()->json(['error' => 'Error al generar el PDF'], 500);
        }
    }


    public function obtenerComprobantesPorSitio($id_sitio)
    {
        try {
            // Obtener los comprobantes relacionados con el sitio específico
            $comprobantes = Comprobante::whereHas('lote.sitio', function ($query) use ($id_sitio) {
                $query->where('id_sitio', $id_sitio);
            })->get(['id_comprobante']); // Obtener solo los IDs de los comprobantes
            
            // Contar la cantidad de comprobantes
            $cantidadComprobantes = $comprobantes->count();
    
            // Crear un array con las IDs de los comprobantes
            $comprobanteIds = $comprobantes->pluck('id_comprobante');
    
            return response()->json([
                'success' => true,
                'cantidad_comprobantes' => $cantidadComprobantes,
                'comprobante_ids' => $comprobanteIds // Devolver las IDs
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener comprobantes: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener los comprobantes'], 500);
        }
    }
    
    // Mostrar un comprobante específico
    public function show($id)
    {
        $comprobante = Comprobante::find($id);

        if (!$comprobante) {
            return response()->json(['error' => 'Comprobante no encontrado'], 404);
        }

        return response()->json($comprobante);
    }
   

}
