<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnidadMedida extends Model
{
    use HasFactory;

    protected $primaryKey = 'codigo_medida';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'nombre',
    ];

    public function getRouteKeyName()
    {
        return 'codigo_medida';
    }

    public function productos()
    {
        return $this->hasMany(Producto::class, 'unidad_codigo', 'codigo_medida');
    }
}
