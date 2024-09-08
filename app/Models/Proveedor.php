<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proveedor extends Model
{

    use SoftDeletes;
    protected $dates = ['deleted_at'];
    
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
