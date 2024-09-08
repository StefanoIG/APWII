<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Retorno;
use Illuminate\Support\Facades\Validator;

class RetornoController extends Controller
{
    // Obtener todos los retornos
    public function index()
    {
        try {
            $retornos = Retorno::all();
            return response()->json($retornos);
        } catch (\Exception $e) {
            Log::error('Error al obtener retornos: ' . $e->getMessage());
            return response()->json(['error' => 'Error en el servidor'], 500);
        }
    }

    // Crear un nuevo retorno
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'id_comprobante' => 'required|exists:comprobante,id_comprobante', // Cambiado a 'comprobante'
                'id_producto' => 'required|exists:producto,id_producto', // Cambiado a 'producto'
                'fecha_retorno' => 'required|date',
                'cantidad' => 'required|integer',
                'motivo_retorno' => 'required|string|max:255',
                'estado_retorno' => 'required|string|max:255',
                'isActive' => 'required|boolean'
            ]);

            $retorno = Retorno::create($validatedData);

            return response()->json($retorno, 201);
        } catch (\Exception $e) {
            Log::error('Error al crear retorno: ' . $e->getMessage());
            return response()->json(['error' => 'Error en el servidor', 'details' => $e->getMessage()], 500);
        }
    }

    // Mostrar un retorno específico
    public function show(string $id)
    {
        $retorno = Retorno::find($id);

        if(!$retorno) {
            return response()->json(['error' => 'Retorno no encontrado'], 404);
        }

        return response()->json($retorno);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }
}
