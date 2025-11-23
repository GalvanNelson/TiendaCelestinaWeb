<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $primaryKey = 'codigo_producto';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio_unitario',
        'stock',
        'categoria_codigo',
        'unidad_codigo',
        'imagen',
    ];

    /**
     * Obtener la clave de ruta para el modelo
     */
    public function getRouteKeyName()
    {
        return 'codigo_producto';
    }

    // Relaciones
    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'categoria_codigo', 'codigo_categoria');
    }

    public function unidadMedida()
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_codigo', 'codigo_unidad');
    }
}
