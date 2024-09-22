<?php

namespace App\Http\Controllers;

use App\Models\Comprobante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf; // Asegúrate de importar DomPDF
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB; // Importar la clase DB
use App\Models\Lote; // Importar el modelo Lote
use Illuminate\Support\Facades\Auth; // Importar la clase Auth

class ComprobanteController extends Controller
{
    // Obtener todos los comprobantes
    public function index()
    {
        $user = Auth::user();
        Log::info('Usuario autenticado: ' . $user->id);
    
        // Verificar permiso
        if (!$this->verificarPermiso('Puede ver comprobantes')) {
            return response()->json(['error' => 'No tienes permiso para ver comprobantes'], 403);
        }
    
        try {
            $comprobantes = Comprobante::all();
            return response()->json($comprobantes);
        } catch (\Exception $e) {
            Log::error('Error al obtener comprobantes: ' . $e->getMessage());
            return response()->json(['error' => 'Error en el servidor'], 500);
        }
    }

  // Crear un nuevo comprobante
  public function store(Request $request)
  {
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
          $validatedData = $validator->validated(); // Aquí se asigna la variable
  
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
    public function generarPDF($id)
    {
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


    public function obtenerComprobantesPorSitio($id_sitio)
    {
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
    public function show($id)
    {
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

    
    public function paginatedIndex(Request $request)
    {
        // Validar la solicitud
        $validator = Validator::make($request->all(), [
            'fecha_emision' => 'sometimes|date',
            'id_lote' => 'sometimes|exists:lote,id_lote',
            'cantidad' => 'sometimes|integer',
            'precio_total' => 'sometimes|numeric',
            'deleted_at' => 'sometimes|boolean', // Use boolean to filter active/inactive
            'per_page' => 'sometimes|integer|min:1|max:100', // Limitar el número de resultados por página
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Obtener los datos validados
        $validatedData = $validator->validated();

        // Construir la consulta con los filtros opcionales
        $query = Comprobante::query();

        if (isset($validatedData['fecha_emision'])) {
            $query->whereDate('fecha_emision', $validatedData['fecha_emision']);
        }

        if (isset($validatedData['id_lote'])) {
            $query->where('id_lote', $validatedData['id_lote']);
        }

        if (isset($validatedData['cantidad'])) {
            $query->where('cantidad', $validatedData['cantidad']);
        }

        if (isset($validatedData['precio_total'])) {
            $query->where('precio_total', $validatedData['precio_total']);
        }

        // Filtrar por deleted_at
        if (isset($validatedData['deleted_at'])) {
            if ($validatedData['deleted_at']) {
                $query->onlyTrashed(); // Solo comprobantes eliminados
            } else {
                $query->withTrashed(); // Todos los comprobantes, incluidos los eliminados
            }
        }

        // Obtener el número de resultados por página, por defecto 15
        $perPage = $validatedData['per_page'] ?? 15;

        // Obtener los resultados paginados
        $comprobantes = $query->paginate($perPage);

        return response()->json($comprobantes);
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

/**
 * Verifica si el usuario autenticado tiene un permiso específico.
 */
private function verificarPermiso($permisoNombre)
{
    try {
        $user = Auth::user();
        $roles = $user->roles;

        // Agregar log para verificar los roles asignados
        Log::info('Roles del usuario: ' . json_encode($roles->pluck('nombre')));

        // Si el usuario tiene el rol de administrador, dar acceso automáticamente
        foreach ($roles as $rol) {
            if ($rol->nombre === 'Administrador') {
                return true; // Administrador tiene acceso completo
            }
        }

        // Si no es administrador, verificar el permiso específico
        foreach ($roles as $rol) {
            if ($rol->permisos()->where('nombre', $permisoNombre)->exists()) {
                return true;
            }
        }

        return false; // No tiene permisos
    } catch (\Exception $e) {
        Log::error('Error en verificarPermiso: ' . $e->getMessage());
        return false;
    }
}

    /**
     * Verifica si el usuario autenticado tiene un rol específico.
     *
     * @param string $rolNombre
     * @return bool
     */
    private function verificarRol($rolNombre)
    {
        try {
            $user = Auth::user();
            $roles = $user->roles;

            foreach ($roles as $rol) {
                if ($rol->nombre === $rolNombre) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Error en verificarRol: ' . $e->getMessage());
            return false;
        }
    }

}
