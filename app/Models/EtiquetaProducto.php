<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtiquetaProducto extends Model
{
    protected $table = 'etiqueta_producto';

    protected $fillable = [
        'producto_id',
        'etiqueta_id',
    ];

    // No es necesario definir relaciones aquí ya que es una tabla pivote,
    // pero puedes agregar métodos si necesitas lógica específica para este modelo.
}
