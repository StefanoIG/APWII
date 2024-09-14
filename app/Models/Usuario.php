<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


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
    public function setPasswordAttribute($value) // Cambiamos a 'setPasswordAttribute'
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

    // Relación con los empleados
    public function empleados()
    {
        return $this->belongsToMany(Usuario::class, 'owner_empleado', 'owner_id', 'empleado_id');
    }

    // Relación con los owners
    public function owners()
    {
        return $this->belongsToMany(Usuario::class, 'owner_empleado', 'empleado_id', 'owner_id');
    }
    public function planes(): BelongsTo
    {
        return $this->belongsTo(Planes::class);
    }
}
