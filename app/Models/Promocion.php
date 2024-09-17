<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promocion extends Model
{
    use SoftDeletes;
    protected $table = 'promociones';
    protected $fillable = ['codigo', 'descuento', 'fecha_inicio', 'fecha_fin', 'activo'];

    // Relaciones con otros modelos si aplican
}
