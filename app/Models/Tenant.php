<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    //proteger la tabla
    protected $table = 'tenants';
    
    protected $fillable = [
        'id',
        'name',
        'database_path',
        'data',
    ];

    // Relación muchos a muchos con usuarios
    public function usuarios()
    {
        return $this->belongsToMany('App\Models\Usuario', 'tenant_usuario', 'tenant_id', 'usuario_id');
    }
}
