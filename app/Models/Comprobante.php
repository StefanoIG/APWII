<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comprobante extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    // Tabla asociada al modelo
    protected $table = 'comprobante';
    protected $primaryKey = 'id_comprobante';


    // Atributos que se pueden asignar de manera masiva
    protected $fillable = [
        'fecha_emision',
        'id_lote',
        'usuario_id',
        'id_producto',
        'cantidad',
        'precio_total',
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
    
    // Relación con Lote
    public function lote()
    {
        return $this->belongsTo(Lote::class, 'id_lote', 'id_lote');
    }

    //relacion con 
}