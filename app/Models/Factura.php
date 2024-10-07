<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Factura extends Model
{
    use SoftDeletes;
    protected $table = 'facturas';
    protected $fillable = [
        'usuario_id',  // Corregido
        'metodo_pago_id',
        'order_id',
        'order_id_paypal',
        'total',
        'fecha_pago',
        'estado',
        'proxima_fecha_pago',
        'fecha_gracia',
    ];

    // Relación con los detalles de la factura
    public function detalles() {
        return $this->hasMany(DetalleFactura::class, 'factura_id');
    }

    // Relación con el usuario
    public function usuario() {
        return $this->belongsTo(Usuario::class, 'usuario_id'); // Corregido
    }

    // Relación con el método de pago
    public function metodoPago() {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }
}
