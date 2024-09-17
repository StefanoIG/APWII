<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Factura;
use Illuminate\Support\Facades\Validator;

class FacturaController extends Controller
{
    public function paginatedIndex(Request $request)
    {
        // Validar la solicitud
        $validator = Validator::make($request->all(), [
            'usuario_id' => 'sometimes|integer|exists:usuarios,id',
            'metodo_pago_id' => 'sometimes|integer|exists:metodos_pago,id',
            'order_id' => 'sometimes|string|max:255',
            'order_id_paypal' => 'sometimes|string|max:255',
            'total' => 'sometimes|numeric|min:0',
            'fecha_pago' => 'sometimes|date',
            'estado' => 'sometimes|in:pendiente,pagado,cancelado',
            'deleted_at' => 'sometimes|boolean', // Use boolean to filter active/inactive
            'per_page' => 'sometimes|integer|min:1|max:100', // Limitar el número de resultados por página
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Obtener los datos validados
        $validatedData = $validator->validated();

        // Construir la consulta con los filtros opcionales
        $query = Factura::query();

        if (isset($validatedData['usuario_id'])) {
            $query->where('usuario_id', $validatedData['usuario_id']);
        }

        if (isset($validatedData['metodo_pago_id'])) {
            $query->where('metodo_pago_id', $validatedData['metodo_pago_id']);
        }

        if (isset($validatedData['order_id'])) {
            $query->where('order_id', 'like', '%' . $validatedData['order_id'] . '%');
        }

        if (isset($validatedData['order_id_paypal'])) {
            $query->where('order_id_paypal', 'like', '%' . $validatedData['order_id_paypal'] . '%');
        }

        if (isset($validatedData['total'])) {
            $query->where('total', $validatedData['total']);
        }

        if (isset($validatedData['fecha_pago'])) {
            $query->where('fecha_pago', $validatedData['fecha_pago']);
        }

        if (isset($validatedData['estado'])) {
            $query->where('estado', $validatedData['estado']);
        }

        // Filtrar por deleted_at
        if (isset($validatedData['deleted_at'])) {
            if ($validatedData['deleted_at']) {
                $query->onlyTrashed(); // Solo facturas eliminadas
            } else {
                $query->withTrashed(); // Todas las facturas, incluidas las eliminadas
            }
        }

        // Obtener el número de resultados por página, por defecto 15
        $perPage = $validatedData['per_page'] ?? 15;

        // Obtener los resultados paginados
        $facturas = $query->paginate($perPage);

        return response()->json($facturas);
    }
}
