<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $fillable = [
        'name',
        'label',
        'icon',
        'route',
        'url',
        'permission',
        'parent_id',
        'order',
        'is_active',
        'is_separator',
        'separator_label',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_separator' => 'boolean',
    ];

    /**
     * Obtener el padre del menú
     */
    public function parent()
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    /**
     * Obtener los hijos del menú
     */
    public function children()
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('order');
    }

    /**
     * Scope para obtener solo items activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para obtener solo items de nivel principal
     */
    public function scopeRootItems($query)
    {
        return $query->whereNull('parent_id')->orderBy('order');
    }

    /**
     * Verificar si el usuario tiene permiso para ver este item
     */
    public function userHasPermission($user)
    {
        if (!$this->permission) {
            return true; // Si no requiere permiso, todos pueden verlo
        }

        return $user->can($this->permission);
    }
}
