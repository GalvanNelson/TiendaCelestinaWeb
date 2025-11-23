<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;

    protected $primaryKey = 'codigo_categoria';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'nombre',
    ];

    public function getRouteKeyName()
    {
        return 'codigo_categoria';
    }

    public function productos()
    {
        return $this->hasMany(Producto::class, 'categoria_codigo', 'codigo_categoria');
    }
}
