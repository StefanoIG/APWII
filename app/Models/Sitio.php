<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sitio extends Model
{
    use HasFactory;

    // Nombre de la tabla
    protected $table = 'sitio';

    // Clave primaria
    protected $primaryKey = 'id_sitio';

    // Atributos que se pueden asignar masivamente
    protected $fillable = [
        'nombre_sitio',
        'direccion',
        'ciudad',
        'pais'
    ];
}
