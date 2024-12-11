<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use SoftDeletes;
    
    protected $table = 'producto';
    protected $primaryKey = 'id_producto';

    protected $fillable = [
        'nombre_producto',
        'tipo_producto',
        'descripcion_producto',
        'precio',
        'codigo_barra'
    ];

    // Relación muchos a muchos con Etiqueta
    public function etiquetas()
    {
        return $this->belongsToMany(Etiqueta::class, 'etiqueta_producto', 'producto_id', 'etiqueta_id');
    }
}
