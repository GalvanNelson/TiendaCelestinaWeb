<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntradaStock extends Model
{
    use HasFactory;

    protected $primaryKey = 'codigo_entrada';
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
        'cantidad' => 'integer',
        'codigo_producto' => 'integer',
    ];

    public function getRouteKeyName()
    {
        return 'codigo_entrada';
    }

    /**
     * Relación con Producto
     */
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'codigo_producto', 'codigo_producto');
    }
}
