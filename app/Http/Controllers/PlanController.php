<?php

namespace App\Http\Controllers;

use App\Models\Planes;
use Illuminate\Http\Request;
//importar validator
use Illuminate\Support\Facades\Validator;

class PlanController extends Controller
{
    //Get de planes totales
    public function index()
    {
        $planes = Planes::all();
        return response()->json($planes);
    }

    //Get de un plan en especifico
    public function show($id)
    {
        $plan = Planes::find($id);
        return response()->json($plan);
    }

    //Crear un plan
    public function store(Request $request)
    {
       //validar request
         $validator = Validator::make($request->all(), [
          'name' => 'required',
          'price' => 'required',
          'duration' => 'required',
          'features' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $plan = Planes::create($request->all());
        return response()->json($plan, 201);

    }

    //Actualizar un plan
    public function update(Request $request, $id)
    {
        //validar request
        $validator = Validator::make($request->all(), [
          'name' => 'required',
          'price' => 'required',
          'duration' => 'required',
          'features' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $plan = Planes::findOrFail($id);
        $plan->update($request->all());
        return response()->json($plan, 200);
    }

    //eleminar un plan
    public function delete($id)
    {
        Planes::findOrFail($id)->delete();
        return response()->json(['message' => 'Plan deleted'], 200);
    }


}
