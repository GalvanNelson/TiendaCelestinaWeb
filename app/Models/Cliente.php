<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    /**
     * La tabla asociada al modelo
     */
    protected $table = 'clientes';

    /**
     * La clave primaria de la tabla
     */
    protected $primaryKey = 'codigo_cliente';

    /**
     * Indica si la clave primaria es auto-incremental
     */
    public $incrementing = false;

    /**
     * El tipo de la clave primaria
     */
    protected $keyType = 'int';

    /**
     * Los atributos que son asignables masivamente
     */
    protected $fillable = [
        'codigo_cliente',
        'nombre',
        'apellido',
        'email',
        'password',
        'telefono',
        'domicilio',
    ];

    /**
     * Los atributos que deben ocultarse para arrays
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Los atributos que deben ser convertidos
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Accesor para obtener el nombre completo
     */
    public function getNombreCompletoAttribute()
    {
        return "{$this->nombre} {$this->apellido}";
    }
}
