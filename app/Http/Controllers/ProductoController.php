<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\Request;
//validator
use Illuminate\Support\Facades\Validator;


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
        //validacion de datos de la request
        $validator = Validator::make($request->all(), [
            'nombre_producto' => 'required|string|max:255',
            'tipo_producto' => 'required|string|max:255',
            'descripcion_producto' => 'required|string|max:255',
            'precio' => 'required|numeric',
            'cantidad' => 'required|integer',
            'id_etiqueta' => 'integer',
            'isActive' => 'required|boolean',
        ]);
        //si fallan
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
        //si no fallan
        $producto = Producto::create($request->all());  
        
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
