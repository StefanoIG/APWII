<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rol extends Model
{

    protected $table = 'roles';  // Especifica el nombre de la tabla si es diferente de la convención singular del nombre del modelo
    use SoftDeletes;

    protected $fillable = ['nombre', 'descripcion'];

    // Relación muchos a muchos con permisos
    public function permisos()
    {
        return $this->belongsToMany(Permiso::class, 'rol_permiso', 'rol_id', 'permiso_id');
    }

    // Relación muchos a muchos con usuarios
    public function usuarios()
    {
        return $this->belongsToMany(Usuario::class, 'usuarios_rol', 'rol_id', 'usuario_id');
    }
}
