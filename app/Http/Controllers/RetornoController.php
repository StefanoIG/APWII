<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Retorno;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class RetornoController extends Controller
{
    /**
     * Establecer la conexión al tenant correspondiente usando el nombre de la base de datos.
     */
    protected function setTenantConnection(Request $request)
    {
        $tenantDatabase = $request->header('X-Tenant');

        if (!$tenantDatabase) {
            abort(400, 'El encabezado X-Tenant es obligatorio.');
        }

        config(['database.connections.tenant' => [
            'driver' => 'sqlite',
            'database' => database_path('tenants/' . $tenantDatabase),
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);

        DB::purge('tenant');
        DB::reconnect('tenant');
        DB::setDefaultConnection('tenant');
    }

    /**
     * Verificar si el usuario autenticado tiene un permiso específico.
     */
    private function verificarPermiso($permisoNombre)
    {
        $user = Auth::user();

        if (!$user) {
            Log::error('Usuario no autenticado al verificar permiso: ' . $permisoNombre);
            return false;
        }

        $user->load('roles.permisos');

        foreach ($user->roles as $rol) {
            if ($rol->permisos->contains('nombre', $permisoNombre)) {
                return true;
            }
        }

        Log::warning('Permiso no encontrado: ' . $permisoNombre . ' para el usuario: ' . $user->id);
        return false;
    }

    // Obtener todos los retornos
    public function index(Request $request)
    {
        $this->setTenantConnection($request);

        if (!$this->verificarPermiso('Puede ver retornos')) {
            return response()->json(['error' => 'No tienes permiso para ver retornos'], 403);
        }

        try {
            $retornos = Retorno::all();
            return response()->json($retornos, 200);
        } catch (\Exception $e) {
            Log::error('Error al obtener retornos: ' . $e->getMessage());
            return response()->json(['error' => 'Error en el servidor'], 500);
        }
    }

    // Crear un nuevo retorno
    public function store(Request $request)
    {
        $this->setTenantConnection($request);

        // if (!$this->verificarPermiso('Puede crear retornos')) {
        //     return response()->json(['error' => 'No tienes permiso para crear retornos'], 403);
        // }

        $validator = Validator::make($request->all(), [
            'id_comprobante' => 'required|exists:comprobante,id_comprobante',
            'id_producto' => 'required|exists:producto,id_producto',
            'fecha_retorno' => 'required|date',
            'cantidad' => 'required|integer',
            'motivo_retorno' => 'required|string|max:255',
            'estado_retorno' => 'required|string|max:255',
            'isActive' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $retorno = Retorno::create($request->all());
            return response()->json($retorno, 201);
        } catch (\Exception $e) {
            Log::error('Error al crear retorno: ' . $e->getMessage());
            return response()->json(['error' => 'Error en el servidor', 'details' => $e->getMessage()], 500);
        }
    }

    // Mostrar un retorno específico
    public function show(Request $request, string $id)
    {
        $this->setTenantConnection($request);

        $retorno = Retorno::find($id);

        if (!$retorno) {
            return response()->json(['error' => 'Retorno no encontrado'], 404);
        }

        return response()->json($retorno, 200);
    }
}
