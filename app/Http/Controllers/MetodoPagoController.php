<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
use Illuminate\Http\Request;

class MetodoPagoController extends Controller
{
    public function index()
    {
        $metodosPago = MetodoPago::all();
        return response()->json($metodosPago);
    }

    public function store(Request $request)
    {
        

        $metodoPago = MetodoPago::create($request->all());
        return response()->json($metodoPago, 201);
    }

    public function show($id)
    {
        $metodoPago = MetodoPago::findOrFail($id);
        return response()->json($metodoPago);
    }

    public function update(Request $request, $id)
    {
        $metodoPago = MetodoPago::findOrFail($id);
        $metodoPago->update($request->all());
        return response()->json($metodoPago);
    }

    public function destroy($id)
    {
        MetodoPago::destroy($id);
        return response()->json(null, 204);
    }
}
