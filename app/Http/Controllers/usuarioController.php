<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

//Validator for method post
use Illuminate\Support\Facades\Validator;

class usuarioController extends Controller
{
    //
    public function index()
    {
        return view('usuario.index');
    }
    public function create()
    {  
        //validation
        $validator = Validator::make($request->all(), [
            'nombre' => 'required',
            'apellido' => 'required',
            'telefono' => 'required',
            'cedula' => 'required',
            'correo_electronico' => 'required',
            'contrasena' => 'required',
        ]);
        if ($validator->fails()) {
            return redirect('usuario/create')
                        ->withErrors($validator)
                        ->withInput();
        }
        //save data
        $usuario = new Usuario;
        $usuario->nombre = $request->nombre;
        $usuario->apellido = $request->apellido;
        $usuario->telefono = $request->telefono;
        $usuario->cedula = $request->cedula;
        $usuario->correo_electronico = $request->correo_electronico;
        $usuario->contrasena = $request->contrasena;
        $usuario->rol = $request->rol;
        $usuario->save();
        return redirect('usuario')->with('success', 'Information has been added');

    }
}
