<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetalleFactura extends Model
{
    use SoftDeletes;
    protected $table = 'detalle_factura';
    protected $fillable = [
        'factura_id',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'subtotal',
        'dia_facturacion',
    ];

    // Relación con la factura
    public function factura()
    {
        return $this->belongsTo(Factura::class, 'factura_id');
    }
}
