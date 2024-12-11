<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Etiqueta extends Model
{
    use SoftDeletes;

    protected $table = 'etiqueta';
    protected $primaryKey = 'id_etiqueta';

    protected $fillable = [
        'nombre',
        'color_hex',
        'descripcion',
        'categoria',
        'prioridad',
    ];

    // Relación muchos a muchos con Producto
    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'etiqueta_producto', 'etiqueta_id', 'producto_id');
    }

    public function lotes()
    {
        return $this->belongsToMany(Lote::class, 'etiqueta_lote', 'etiqueta_id', 'lote_id');
    }
}

