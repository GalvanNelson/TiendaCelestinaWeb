<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuentaPorCobrar extends Model
{
    use HasFactory;

    protected $table = 'cuentas_por_cobrar';
    protected $primaryKey = 'codigo_cuenta';

    protected $fillable = [
        'venta',
        'monto_total',
        'monto_pagado',
        'saldo_pendiente',
        'fecha_vencimiento',
        'estado',
    ];

    protected $casts = [
        'monto_total' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
        'fecha_vencimiento' => 'date',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta', 'codigo_venta');
    }

    public function cuotas()
    {
        return $this->hasMany(Cuota::class, 'cuenta_por_cobrar', 'codigo_cuenta');
    }

    // Actualizar estado según saldo
    public function actualizarEstado()
    {
        if ($this->saldo_pendiente <= 0) {
            $this->estado = 'pagado';
        } elseif ($this->monto_pagado > 0) {
            $this->estado = 'parcial';
        } elseif ($this->fecha_vencimiento && $this->fecha_vencimiento < now()) {
            $this->estado = 'vencido';
        } else {
            $this->estado = 'pendiente';
        }
        $this->save();
    }
}
