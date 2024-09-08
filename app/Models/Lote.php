<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Lote extends Model
{
    use SoftDeletes;
    protected $dates = ['deleted_at'];
    
    protected $table = 'lote';

    protected $primaryKey = 'id_lote';


    // Atributos que se pueden asignar de manera masiva
    protected $fillable = [
        'producto_id',
        'proveedor_id',
        'id_sitio',
        'codigo_lote',
        'fecha_fabricacion',
        'fecha_caducidad',
        'cantidad',
        'expirable',
        'isActive'
    ];

    // Relación con Producto
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id', 'id_producto');
    }

    // Relación con Proveedor
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id', 'id');
    }
    //relacion con sitio
    public function sitio()
    {
        return $this->belongsTo(Sitio::class, 'id_sitio', 'id_sitio');
    }
}
