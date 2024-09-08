<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Usuario extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $table = 'usuarios';  // Especifica el nombre de la tabla si es diferente de la convención singular del nombre del modelo

    /**
     * Atributos asignables masivamente.
     */
    protected $fillable = [
        'nombre', 
        'apellido', 
        'telefono', 
        'cedula', 
        'correo_electronico', 
        'password', 
        'rol'
    ];

    /**
     * Atributos que se deben ocultar en las respuestas JSON.
     */
    protected $hidden = [
        'password', 
        'remember_token'
    ];

    /**
     * Modificar el nombre del campo 'password' a 'password'.
     */
    public function setpasswordAttribute($value) // Cambiamos a 'setpasswordAttribute'
    {
        $this->attributes['password'] = bcrypt($value);  // Almacena la contraseña encriptada
    }

    /**
     * Implementación del JWTSubject para manejar JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Sobreescribimos getAuthPassword para que use 'password' en lugar de 'password'
     */
    public function getAuthPassword()
    {
        return $this->attributes['password'];
    }
}

