<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Planes extends Model
{
    use SoftDeletes;

    //definir pk
    protected $primaryKey = 'id_plan';
    protected $fillable = ['name', 'price', 'duration', 'features'];
    
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
