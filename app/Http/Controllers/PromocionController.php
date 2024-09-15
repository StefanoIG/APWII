<?php

namespace App\Http\Controllers;

use App\Models\Promocion;
use Illuminate\Http\Request;

class PromocionController extends Controller
{
    public function index() {
        $promociones = Promocion::all();
        return response()->json($promociones);
    }

    public function store(Request $request) {
        $promocion = Promocion::create($request->all());
        return response()->json($promocion, 201);
    }

    public function show($id) {
        $promocion = Promocion::findOrFail($id);
        return response()->json($promocion);
    }

    public function update(Request $request, $id) {
        $promocion = Promocion::findOrFail($id);
        $promocion->update($request->all());
        return response()->json($promocion);
    }

    public function destroy($id) {
        Promocion::destroy($id);
        return response()->json(null, 204);
    }
}


