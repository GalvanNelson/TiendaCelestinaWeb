<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

if (!function_exists('can_user')) {
    /**
     * Verificar si el usuario autenticado tiene un permiso específico
     *
     * @param string|array $permission
     * @return bool
     */
    function can_user(string|array $permission): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user instanceof User) {
            return false;
        }

        return $user->hasAnyPermission((array) $permission);
    }
}

if (!function_exists('has_role')) {
    /**
     * Verificar si el usuario autenticado tiene un rol específico
     *
     * @param string|array $role
     * @return bool
     */
    function has_role(string|array $role): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user instanceof User) {
            return false;
        }

        return $user->hasAnyRole((array) $role);
    }
}

if (!function_exists('is_propietario')) {
    /**
     * Verificar si el usuario autenticado es propietario
     *
     * @return bool
     */
    function is_propietario(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user instanceof User) {
            return false;
        }

        return $user->isPropietario();
    }
}

if (!function_exists('is_vendedor')) {
    /**
     * Verificar si el usuario autenticado es vendedor
     *
     * @return bool
     */
    function is_vendedor(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user instanceof User) {
            return false;
        }

        return $user->isVendedor();
    }
}

if (!function_exists('user_permissions')) {
    /**
     * Obtener todos los permisos del usuario autenticado
     *
     * @return array
     */
    function user_permissions(): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user instanceof User) {
            return [];
        }

        return $user->getAllPermissions();
    }
}

if (!function_exists('user_roles')) {
    /**
     * Obtener todos los roles del usuario autenticado
     *
     * @return array
     */
    function user_roles(): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user instanceof User) {
            return [];
        }

        return $user->roles->pluck('name')->toArray();
    }
}
