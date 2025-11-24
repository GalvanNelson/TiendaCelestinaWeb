<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalidaStock extends Model
{
    use HasFactory;

    protected $primaryKey = 'codigo_salida';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'codigo_producto',
        'cantidad',
        'motivo',
        'usuario',
        'fecha',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    /**
     * Relación con Producto
     */
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'codigo_producto', 'codigo_producto');
    }
}
