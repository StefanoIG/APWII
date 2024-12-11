<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EtiquetaLote extends Model
{
    use SoftDeletes;
    use HasFactory;
    protected $table = 'etiqueta_lote';

    protected $fillable = [
        'lote_id',
        'etiqueta_id',
    ];
}
