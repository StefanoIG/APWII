<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Etiqueta extends Model
{
    use HasFactory;

    protected $table = 'etiquetas';

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array
     */
    protected $fillable = [
        'nombre',
        'color_hex',
        'descripcion',
        'categoria',
        'prioridad',
        'isActive',
    ];

    /**
     * Los atributos que deben ser casteados a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'isActive' => 'boolean',
    ];
}
