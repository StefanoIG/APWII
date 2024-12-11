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
        'id_producto',
        'id_proveedor',
        'id_sitio',
        'codigo_lote',
        'fecha_fabricacion',
        'fecha_caducidad',
        'cantidad',
        'expirable',
        'codigo_barra',
    ];

    // Relación con Producto
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }

    // Relación con Proveedor
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor', 'id_proveedor');
    }
    
    // Relación con Sitio
    public function sitio()
    {
        return $this->belongsTo(Sitio::class, 'id_sitio', 'id_sitio');
    }
    
    // Relación con Etiqueta
    public function etiquetas()
    {
        return $this->belongsToMany(Etiqueta::class, 'etiqueta_lote', 'lote_id', 'etiqueta_id');
    }
}
