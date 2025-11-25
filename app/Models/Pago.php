<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    use HasFactory;

    protected $primaryKey = 'codigo_pago';

    protected $fillable = [
        'venta',
        'monto',
        'fecha_pago',
        'metodo_pago',
        'referencia',
        'usuario_registro',
        'notas',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_pago' => 'datetime',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta', 'codigo_venta');
    }

    public function usuarioRegistro()
    {
        return $this->belongsTo(User::class, 'usuario_registro');
    }
}
