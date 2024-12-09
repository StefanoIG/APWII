<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EtiquetaLote extends Model
{
    use HasFactory;
    protected $table = 'etiqueta_lote';

    protected $fillable = [
        'lote_id',
        'etiqueta_id',
    ];
}
