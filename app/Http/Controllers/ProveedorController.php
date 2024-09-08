<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Proveedor;
//validator
use Illuminate\Support\Facades\Validator;


class ProveedorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //Retorna todos los proveedores
        return Proveedor::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //Validacion de los datos
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'email' => 'required|string|max:255',
            'telefono' => 'required|string|max:255',
            'Cuidad' => 'required|string|max:255',
            'Activo' => 'required|boolean',
            'isActive' => 'required|boolean',
        ]);

        //Si la validacion falla
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        //Si la validacion es correcta
        $proveedor = Proveedor::create($request->all());
        return response()->json($proveedor, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //Mostrar un proveedor en base a la id que se envia
        return Proveedor::find($id);
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
