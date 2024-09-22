<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permiso extends Model
{

    protected $table = 'permisos';  // Especifica el nombre de la tabla si es diferente de la convención singular del nombre del modelo

    use SoftDeletes;

    protected $fillable = ['nombre', 'descripcion'];

    // Relación muchos a muchos con roles
    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'rol_permiso', 'permiso_id', 'rol_id');
    }
}
