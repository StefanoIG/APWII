<?php

namespace App\Http\Controllers;

use App\Models\Comprobante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use App\Models\Lote;
use Illuminate\Support\Facades\Auth;

class ComprobanteController extends Controller
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

    // Obtener todos los comprobantes
    public function index(Request $request)
    {
        // Establecer la conexión al tenant
        $this->setTenantConnection($request);

        // Verificar permiso
        if (!$this->verificarPermiso('Puede ver comprobantes')) {
            return response()->json(['error' => 'No tienes permiso para ver comprobantes'], 403);
        }

        $comprobantes = Comprobante::all();

        return response()->json($comprobantes);
    }

    // Crear un nuevo comprobante
    public function store(Request $request)
    {
        // Establecer la conexión al tenant
        $this->setTenantConnection($request);

        // Verificar permiso
        // if (!$this->verificarPermiso('Puede crear comprobantes')) {
        //     return response()->json(['error' => 'No tienes permiso para crear comprobantes'], 403);
        // }

        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'fecha_emision' => 'required|date',
                'id_lote' => 'required|exists:lote,id_lote',
                'usuario_id' => 'required|exists:usuarios,id',
                'id_producto' => 'required|exists:producto,id_producto',
                'cantidad' => 'required|integer',
                'precio_total' => 'required|numeric'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Obtener datos validados
            $validatedData = $validator->validated();

            // Verificar si el lote está expirado
            if (!$this->verificarLoteExpirado($validatedData['id_lote'])) {
                return response()->json(['error' => 'No se pueden extraer productos de un lote expirado.'], 400);
            }

            // Verificar que el lote tenga suficientes productos
            $lote = Lote::findOrFail($validatedData['id_lote']);
            if ($lote->cantidad < $validatedData['cantidad']) {
                return response()->json(['error' => 'El lote no tiene suficientes productos.'], 400);
            }

            // Crear el comprobante
            $comprobante = Comprobante::create($validatedData);

            // Actualizar la cantidad de productos en el lote
            $lote->cantidad -= $validatedData['cantidad'];
            $lote->save();

            DB::commit();

            return response()->json($comprobante, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear comprobante: ' . $e->getMessage());
            return response()->json(['error' => 'Error en el servidor', 'details' => $e->getMessage()], 500);
        }
    }

    // Método para mostrar el comprobante en PDF
    public function generarPDF(Request $request, $id)
    {
        // Establecer la conexión al tenant
        $this->setTenantConnection($request);

        // Verificar permiso
        // if (!$this->verificarPermiso('Puede generar PDF de comprobante')) {
        //     return response()->json(['error' => 'No tienes permiso para generar el PDF'], 403);
        // }

        try {
            // Obtener el comprobante por ID, junto con las relaciones de lote, que a su vez tiene la relación con sitio
            $comprobante = Comprobante::with(['usuario', 'producto', 'lote.sitio'])->findOrFail($id);

            // Cálculo del subtotal
            $subtotal = $comprobante->precio_total;

            // Cálculo del IVA (15%)
            $iva = $subtotal * 0.15;

            // Cálculo del monto final
            $monto_final = $subtotal + $iva;

            // Agregar los cálculos al comprobante para pasarlos a la vista
            $comprobante->subtotal = $subtotal;
            $comprobante->iva = $iva;
            $comprobante->monto_final = $monto_final;

            // Crear el PDF usando la vista comprobante_pdf.blade.php
            $pdf = Pdf::loadView('comprobante_pdf', compact('comprobante'));

            // Descargar el PDF
            return $pdf->download('comprobante_' . $comprobante->id . '.pdf');
        } catch (\Exception $e) {
            Log::error('Error al generar PDF: ' . $e->getMessage());
            return response()->json(['error' => 'Error al generar el PDF'], 500);
        }
    }

    public function obtenerComprobantesPorSitio(Request $request, $id_sitio)
    {
        // Establecer la conexión al tenant
        $this->setTenantConnection($request);

        // Verificar permiso
        if (!$this->verificarPermiso('Puede ver comprobantes por sitio')) {
            return response()->json(['error' => 'No tienes permiso para ver comprobantes por sitio'], 403);
        }

        try {
            // Obtener los comprobantes relacionados con el sitio específico
            $comprobantes = Comprobante::whereHas('lote.sitio', function ($query) use ($id_sitio) {
                $query->where('id_sitio', $id_sitio);
            })->get(['id_comprobante']); // Obtener solo los IDs de los comprobantes

            // Contar la cantidad de comprobantes
            $cantidadComprobantes = $comprobantes->count();

            // Crear un array con las IDs de los comprobantes
            $comprobanteIds = $comprobantes->pluck('id_comprobante');

            return response()->json([
                'success' => true,
                'cantidad_comprobantes' => $cantidadComprobantes,
                'comprobante_ids' => $comprobanteIds // Devolver las IDs
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener comprobantes: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener los comprobantes'], 500);
        }
    }

    // Mostrar un comprobante específico
    public function show(Request $request, $id)
    {
        // Establecer la conexión al tenant
        $this->setTenantConnection($request);

        // Verificar permiso
        if (!$this->verificarPermiso('Puede ver comprobantes')) {
            return response()->json(['error' => 'No tienes permiso para ver comprobantes'], 403);
        }

        $comprobante = Comprobante::find($id);

        if (!$comprobante) {
            return response()->json(['error' => 'Comprobante no encontrado'], 404);
        }

        return response()->json($comprobante);
    }

    // Función para verificar si el lote tiene la etiqueta "expirada"
    private function verificarLoteExpirado($id_lote)
    {
        $lote = Lote::findOrFail($id_lote);

        // Verificar si el lote tiene la etiqueta "expirada"
        if ($lote->etiquetas()->where('nombre', 'expirada')->exists()) {
            return false;
        }

        return true;
    }
}
