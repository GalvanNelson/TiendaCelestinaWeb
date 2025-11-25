<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Venta extends Model
{
    use HasFactory;

    protected $primaryKey = 'codigo_venta';

    protected $fillable = [
        'numero_venta',
        'cliente_id',
        'vendedor_id',
        'fecha_venta',
        'tipo_pago',
        'subtotal',
        'descuento',
        'total',
        'estado',
        'notas'
    ];

    protected $casts = [
        'fecha_venta' => 'datetime',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Generar numero de venta automaticamente
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($venta) {
            if (empty($venta->numero_venta)) {
                $ultimaVenta = static::orderBy('codigo_venta', 'desc')->first();
                $numero = $ultimaVenta ? intval(substr($ultimaVenta->numero_venta, 2)) + 1 : 1;
                $venta->numero_venta = 'V-' . str_pad($numero, 8, '0', STR_PAD_LEFT);
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'codigo_venta';
    }

    // Relaciones
    public function cliente()
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    public function vendedor()
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }

    public function detalles()
    {
        //return $this->hasMany(DetalleVenta::class, 'venta', 'codigo_venta');
    }

    public function cuentaPorCobrar()
    {
        //return $this->hasOne(CuentaPorCobrar::class, 'venta', 'codigo_venta');
    }

    public function pagos()
    {
        //return $this->hasMany(Pago::class, 'venta', 'codigo_venta');
    }

    // Scopes
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    public function scopeCompletadas($query)
    {
        return $query->where('estado', 'completada');
    }

    public function scopeCredito($query)
    {
        return $query->where('tipo_pago', 'credito');
    }
}
