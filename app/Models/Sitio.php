<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sitio extends Model
{
    use SoftDeletes;
    protected $dates = ['deleted_at'];
    protected $table = 'sitio';
    protected $primaryKey = 'id_sitio';

    // Atributos que se pueden asignar masivamente
    protected $fillable = [
        'nombre_sitio',
        'direccion',
        'ciudad',
        'pais',
        'created_by'
    ];
    
    public function Usuario()
    {
        return $this->belongsTo('App\Models\Usuario', 'created_by');
    }
    

}
