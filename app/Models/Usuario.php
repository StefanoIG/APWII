<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Usuario extends Authenticatable implements JWTSubject
{
    use SoftDeletes;
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

    // Relación con los planes
    public function planes(): BelongsTo
    {
        return $this->belongsTo(Planes::class);
    }

    // Nueva relación muchos a muchos con roles
    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'usuario_rol', 'usuario_id', 'rol_id');
    }

    // Verificar si el usuario tiene un permiso a través de roles
    public function tienePermiso($permisoNombre)
    {
        return $this->roles()
            ->whereHas('permisos', function ($query) use ($permisoNombre) {
                $query->where('nombre', $permisoNombre);
            })
            ->exists();
    }

    // Asignar un rol al usuario
    public function asignarRol($rolNombre)
    {
        $rol = Rol::where('nombre', $rolNombre)->firstOrFail();
        $this->roles()->attach($rol->id);
    }

    // Remover un rol del usuario
    public function removerRol($rolNombre)
    {
        $rol = Rol::where('nombre', $rolNombre)->firstOrFail();
        $this->roles()->detach($rol->id);
    }

    // Nueva relación uno a uno o uno a muchos con rol
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function tenants()
    {
        return $this->belongsToMany(Tenant::class, 'usuario_tenant', 'usuario_id', 'tenant_id')
            ->withPivot('rol_id')  // Para manejar roles específicos por tenant
            ->withTimestamps();
    }
    
    // public function tenant()
    // {
    //     return $this->belongsTo(Tenant::class, 'tenant_id');
    // }
}
