<?php

namespace App\Http\Controllers;

use App\Models\Lote;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Etiqueta;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use Illuminate\Support\Facades\Auth;
use App\Models\Producto;

class LoteController extends Controller
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

    // Obtener todos los lotes
    public function index(Request $request)
    {

        $this->setTenantConnection($request);

        $user = Auth::user();

        //verificar permiso
        if ($this->verificarPermiso('Puede ver lotes')) {
            return response()->json(Lote::all());
        } else {
            return response()->json(['error' => 'No tienes permiso para ver lotes'], 403);
        }
        return response()->json(['error' => 'Error al obtener los lotes'], 500);
    }

    // Obtener un lote específico por ID
    public function show($id, Request $request)
    {
        // Establecer la conexión al tenant
        $this->setTenantConnection($request);

        // Verificar permiso
        if (!$this->verificarPermiso('Puede ver lotes')) {
            return response()->json(['error' => 'No tienes permiso para ver lotes'], 403);
        }

        try {
            $lote = Lote::find($id);

            if (!$lote) {
                return response()->json(['message' => 'Lote no encontrado'], 404);
            }

            return response()->json($lote);
        } catch (\Exception $e) {
            Log::error('Error en show: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener el lote'], 500);
        }
    }

    // Crear un nuevo lote
    public function store(Request $request)
    {
        // Validar el nombre de la base de datos del tenant
        $this->setTenantConnection($request);

        // Verificar permiso
        if (!$this->verificarPermiso('Puede crear lotes')) {
            return response()->json(['error' => 'No tienes permiso para crear lotes'], 403);
        }

        // Validar la solicitud con reglas personalizadas
        $validator = Validator::make($request->all(), [
            'id_producto' => 'required|exists:producto,id_producto',
            'id_proveedor' => 'required|exists:proveedor,id_proveedor',
            'fecha_fabricacion' => 'nullable|date',
            'fecha_caducidad' => 'nullable|date',
            'cantidad' => 'required|integer',
            'expirable' => 'required|boolean',
            'id_sitio' => 'required|exists:sitio,id_sitio'
        ]);

        // Verificar si la validación falla
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Uso de transacciones para asegurar consistencia
        DB::beginTransaction();
        try {
            $validatedData = $validator->validated();
            $lote = new Lote();
            $lote->id_producto = $validatedData['id_producto'];
            $lote->id_proveedor = $validatedData['id_proveedor'];
            $lote->fecha_fabricacion = $validatedData['fecha_fabricacion'] ?? null;
            $lote->cantidad = $validatedData['cantidad'];
            $lote->expirable = $validatedData['expirable'];
            $lote->id_sitio = $validatedData['id_sitio'];

            // Asignar fecha_caducidad solo si expirable es true
            if ($validatedData['expirable']) {
                $lote->fecha_caducidad = $validatedData['fecha_caducidad'];
            }

            // Generar código de barras aleatorio de longitud fija
            $codigoBarra = $this->generarCodigoDeBarras();
            $lote->codigo_lote = $codigoBarra;

            // Guardar el lote
            $lote->save();

            // Si el lote es expirable, crear o encontrar la etiqueta "expirable" y asignarla al producto
            if ($validatedData['expirable']) {
                // Buscar o crear la etiqueta "expirable"
                $etiqueta = Etiqueta::firstOrCreate(
                    ['nombre' => 'expirable'],  // Condición de búsqueda
                    [
                        'color_hex' => '#FF0000',
                        'descripcion' => 'Producto expirable',
                        'categoria' => 'Advertencia',
                        'prioridad' => 'alta',
                    ]
                );

                // Asignar la etiqueta al lote
                $lote->etiquetas()->syncWithoutDetaching([$etiqueta->id_etiqueta]);

                // Obtener el producto
                $producto = Producto::find($validatedData['id_producto']);
                if ($producto) {
                    // Asignar la etiqueta al producto sin eliminar las etiquetas anteriores
                    $producto->etiquetas()->syncWithoutDetaching([$etiqueta->id_etiqueta]);
                }
            }

            DB::commit();
            return response()->json($lote, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear lote: ' . $e->getMessage());
            return response()->json(['error' => 'Error al crear el lote'], 500);
        }
    }

    // Generar un código de barras de longitud fija
    private function generarCodigoDeBarras()
    {
        // Generar un código de barra de 8 caracteres numéricos
        return str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);
    }

    public function verCodigoDeBarras($id_lote, Request $request)
    {
        // Establecer la conexión al tenant
        $this->setTenantConnection($request);

        // Verificar permiso
        if (!$this->verificarPermiso('Puede ver lotes')) {
            return response()->json(['error' => 'No tienes permiso para ver lotes'], 403);
        }

        try {
            $lote = Lote::findOrFail($id_lote);
            //log encontrado
            Log::info('Lote encontrado: ' . json_encode($lote));
            
            if (!$lote->codigo_lote) {
                return response()->json(['error' => 'El lote no tiene un código de barras asignado.'], 404);
            }

            // Generar la imagen del código de barras
            $generatorPNG = new BarcodeGeneratorPNG();
            $image = $generatorPNG->getBarcode($lote->codigo_lote, $generatorPNG::TYPE_CODE_128);

            return response($image)->header('Content-type', 'image/png');
        } catch (ModelNotFoundException $e) {
            Log::error('Lote no encontrado: ' . $e->getMessage());
            return response()->json(['error' => 'Lote no encontrado'], 404);
        } catch (\Exception $e) {
            Log::error('Error al generar el código de barras: ' . $e->getMessage());
            return response()->json(['error' => 'Error al generar el código de barras'], 500);
        }
    }


    // Actualizar un lote existente
    public function update(Request $request, $id)
    {
        // Establecer la conexión al tenant
        $this->setTenantConnection($request);

        // Verificar permiso
        if (!$this->verificarPermiso('Puede actualizar lotes')) {
            return response()->json(['error' => 'No tienes permiso para actualizar lotes'], 403);
        }

        // Validar la solicitud
        $validator = Validator::make($request->all(), [
            'id_producto' => 'sometimes|exists:producto,id_producto',
            'id_proveedor' => 'sometimes|exists:proveedor,id_proveedor',
            'fecha_fabricacion' => 'sometimes|date',
            'fecha_caducidad' => 'sometimes|date',
            'cantidad' => 'sometimes|integer',
            'expirable' => 'sometimes|boolean',
            'id_sitio' => 'sometimes|exists:sitio,id_sitio'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $lote = Lote::findOrFail($id);
            $validatedData = $validator->validated();

            // Actualizar el lote con los nuevos datos
            $lote->update($validatedData);

            // Si el lote es expirable, crear o actualizar la etiqueta "expirable"
            if ($lote->expirable) {
                $etiqueta = Etiqueta::firstOrCreate(
                    ['nombre' => 'expirable'],
                    [
                        'color_hex' => '#FF0000',
                        'descripcion' => 'Producto expirable',
                        'categoria' => 'Advertencia',
                        'prioridad' => 'alta',
                    ]
                );

                // Asignar la etiqueta al lote
                $lote->etiquetas()->syncWithoutDetaching([$etiqueta->id_etiqueta]);
            }

            DB::commit();
            return response()->json($lote, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar lote: ' . $e->getMessage());
            return response()->json(['error' => 'Error al actualizar el lote'], 500);
        }
    }


    // Eliminar un lote existente
    public function destroy($id, Request $request)
    {


        // Establecer la conexión al tenant
        $this->setTenantConnection($request);



        try {
            // Verificar permiso
            if (!$this->verificarPermiso('Puede eliminar lotes')) {
                return response()->json(['error' => 'No tienes permiso para eliminar lotes'], 403);
            }

            // Buscar el lote por ID
            $lote = Lote::find($id);

            if (!$lote) {
                return response()->json(['message' => 'Lote no encontrado'], 404);
            }

            // Eliminar el lote
            $lote->delete();

            return response()->json(['message' => 'Lote eliminado correctamente'], 200);
        } catch (\Exception $e) {
            Log::error('Error en destroy: ' . $e->getMessage());
            return response()->json(['error' => 'Error al eliminar el lote'], 500);
        }
    }

    // Buscar un lote por su código de lote
    public function showByCodigoLote(Request $request)
    {
        $tenantDatabase = $request->header('X-Tenant');

        if (!$tenantDatabase) {
            Log::error('El encabezado X-Tenant es obligatorio.');
            return response()->json(['error' => 'El encabezado X-Tenant es obligatorio.'], 400);
        }

        Log::info('Encabezado X-Tenant recibido: ' . $tenantDatabase);

        // Establecer la conexión a la base de datos del tenant
        $this->setTenantConnection($request);

        try {
            // Validar que el código de lote fue enviado en la solicitud
            $validator = Validator::make($request->all(), [
                'codigo_lote' => 'required|string|exists:lote,codigo_lote',
            ]);

            if ($validator->fails()) {
                Log::warning('Validación fallida: ' . json_encode($validator->errors()));
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $codigoLote = $request->codigo_lote;
            Log::info('Buscando lote con código: ' . $codigoLote);

            // Buscar el lote por código de lote con la información del producto asociado
            $lote = Lote::with('producto')->where('codigo_lote', $codigoLote)->first();

            if (!$lote) {
                Log::warning('Lote no encontrado con código: ' . $codigoLote);
                return response()->json(['message' => 'Lote no encontrado'], 404);
            }

            Log::info('Lote encontrado: ' . json_encode($lote));

            return response()->json([
                'lote' => $lote,
                'producto' => $lote->producto
            ]);
        } catch (\Exception $e) {
            Log::error('Error en showByCodigoLote: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener el lote por código'], 500);
        }
    }

    // Buscar un lote por su código de barras
    public function verificarLotesExpirados()
    {
        try {
            Log::info('Ejecutando la función verificarLotesExpirados');
            // Obtener la fecha y hora actual del servidor
            $fechaActual = now();

            // Buscar lotes expirables que no tengan la etiqueta "expirada" y cuya fecha de caducidad ya haya pasado
            $lotesExpirados = Lote::where('expirable', true)
                ->where('fecha_caducidad', '<', $fechaActual)
                ->whereDoesntHave('etiquetas', function ($query) {
                    $query->where('nombre', 'expirada');
                })
                ->get();

            foreach ($lotesExpirados as $lote) {
                // Crear o encontrar la etiqueta "expirada"
                $etiquetaExpirada = Etiqueta::firstOrCreate(
                    ['nombre' => 'expirada'],  // Condición de búsqueda
                    [
                        'color_hex' => '#FF0000',
                        'descripcion' => 'Producto expirado',
                        'categoria' => 'Advertencia',
                        'prioridad' => 'alta',
                    ]
                );

                // Asignar la etiqueta "expirada" al lote
                $lote->etiquetas()->syncWithoutDetaching([$etiquetaExpirada->id_etiqueta]);

                // Obtener el producto asociado al lote
                $producto = Producto::find($lote->id_producto);

                if ($producto) {
                    // Asignar la etiqueta "expirada" al producto sin eliminar las etiquetas anteriores
                    $producto->etiquetas()->syncWithoutDetaching([$etiquetaExpirada->id_etiqueta]);
                } else {
                    // Manejo de error: Producto no encontrado
                    Log::error("Producto no encontrado con ID: " . $lote->id_producto);
                }

                // Registrar que el lote ha sido marcado como expirado
                Log::info('Lote con ID ' . $lote->id_lote . ' marcado como expirado.');
            }
        } catch (\Exception $e) {
            Log::error('Error en verificarLotesExpirados: ' . $e->getMessage());
        }
    }
}
