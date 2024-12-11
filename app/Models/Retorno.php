<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Retorno extends Model
{
    use SoftDeletes;
    use HasFactory;

    // Tabla asociada al modelo
    protected $table = 'retorno';

    // Atributos que se pueden asignar de manera masiva
    protected $fillable = [
        'id_comprobante',
        'id_producto',
        'fecha_retorno',
        'cantidad',
        'motivo_retorno',
        'estado_retorno',
    ];

    // Relación con Comprobante
    public function comprobante()
    {
        return $this->belongsTo(Comprobante::class, 'id_comprobante', 'id_comprobante');
    }

    // Relación con Producto
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }
}
