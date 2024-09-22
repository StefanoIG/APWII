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

    protected $fillable = [
        'product_id',
        'name',
        'description',
        'status',
        'billing_cycles',
        'payment_preferences',
        'taxes',
        'quantity_supported',
        'id_paypal',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
