<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use SoftDeletes;
    use HasDatabase, HasDomains;

    //proteger la tabla
    protected $table = 'tenants';

    protected $fillable = [
        'id',
        'name',
        'data',
        'database_path',
    ];

    protected $casts = [
        'data' => 'array', // Esto es del paquete Stancl Tenancy, pero puedes omitirlo.
    ];

    // // Sobrescribe el método setAttribute para ignorar "data"
    // public function setAttribute($key, $value)
    // {
    //     if ($key === 'data') {
    //         return $this;
    //     }

    //     return parent::setAttribute($key, $value);
    // }

    // Relación muchos a muchos con usuarios
    public function usuarios()
    {
        return $this->belongsToMany('App\Models\Usuario', 'tenant_usuario', 'tenant_id', 'usuario_id');
    }
}
