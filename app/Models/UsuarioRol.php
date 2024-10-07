<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UsuarioRol extends Model
{
    protected $table = 'usuario_rol';
    use SoftDeletes;
    protected $fillable = ['usuario_id', 'rol_id'];
}
