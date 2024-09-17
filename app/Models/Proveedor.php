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

    

    protected $fillable= [
        "nombre",
        "direccion",
        "email",
        "telefono",
        "Cuidad",
        "Activo",
        "isActive",
        "sitio_id"
    ];

    protected $casts = [
        'isActive' => 'boolean',
    ];
    //relacion de 1 a n a sitios
    public function sitio(){
        return $this->belongsTo(Sitio::class, 'sitio_id');
    }

}
