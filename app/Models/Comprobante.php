<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comprobante extends Model
{
    use HasFactory;
    
    // Tabla asociada al modelo
    protected $table = 'comprobante';

    // Atributos que se pueden asignar de manera masiva
    protected $fillable = [
        'fecha_emision',
        'bodega',
        'usuario_id',
        'id_producto',
        'cantidad',
        'precio_total',
        'isActive'
    ];

    // Relación con Usuario
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'id');
    }

    // Relación con Producto
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto', 'id_producto');
    }
}