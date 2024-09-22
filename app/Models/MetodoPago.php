<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MetodoPago extends Model
{
    use SoftDeletes;
    protected $table = 'metodos_pago';
    protected $fillable = ['nombre_metodo', 'descripcion'];

    // Relaciones con otros modelos si aplican
}
