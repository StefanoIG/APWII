<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    protected $table = 'proveedor';

    use HasFactory;

    protected $fillable= [
        "nombre",
        "direccion",
        "email",
        "telefono",
        "Cuidad",
        "Activo",
        "isActive"
    ];

    protected $casts = [
        'isActive' => 'boolean',
    ];
    
}
