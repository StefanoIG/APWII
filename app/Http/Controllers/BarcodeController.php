<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Lote;
use Illuminate\Http\Request;

class CodigoBarraController extends Controller
{
    public function buscarPorCodigoBarra($codigo_barra)
    {
        // Buscar el producto por código de barra
        $producto = Producto::where('codigo_barra', $codigo_barra)->first();

        if ($producto) {
            return response()->json($producto);
        }

        // Buscar el lote por código de barra
        $lote = Lote::where('codigo_barra', $codigo_barra)->first();

        if ($lote) {
            return response()->json($lote);
        }

        return response()->json(['error' => 'No se encontró ningún producto o lote con ese código de barras'], 404);
    }
}
