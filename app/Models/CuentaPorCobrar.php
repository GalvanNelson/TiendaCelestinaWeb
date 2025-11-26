<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CuentaPorCobrar extends Model
{
    use HasFactory;

    protected $table = 'cuenta_por_cobrars';
    protected $primaryKey = 'codigo_cuenta';

    protected $fillable = [
        'venta',
        'monto_total',
        'monto_pagado',
        'saldo_pendiente',
        'fecha_vencimiento',
        'estado',
        'notas'
    ];

    protected $casts = [
        'monto_total' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
        'fecha_vencimiento' => 'date',
    ];

    /**
     * Relación con venta
     */
    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta', 'codigo_venta');
    }

    /**
     * Relación con pagos
     */
    public function pagos()
    {
        return $this->hasMany(Pago::class, 'venta', 'venta');
    }

    /**
     * Actualizar estado de la cuenta
     */
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
