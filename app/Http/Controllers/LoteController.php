<?php

namespace App\Http\Controllers;

use App\Models\Lote;
use Illuminate\Http\Request;

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
            'proveedor_id' => 'required|exists:proveedor,id',
            'codigo_lote' => 'required|string|max:255',
            'fecha_fabricacion' => 'nullable|date',
            'fecha_caducidad' => 'nullable|date',
            'cantidad' => 'required|integer',
            'espirable' => 'required|boolean',
            'isActive' => 'required|boolean'
        ]);

        // Crear un nuevo lote
        $lote = Lote::create($request->all());

        return response()->json($lote, 201);
    }
}
