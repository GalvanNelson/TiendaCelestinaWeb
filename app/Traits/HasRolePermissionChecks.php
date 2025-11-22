<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

trait HasRolePermissionChecks
{
    /**
     * Verificar si el usuario autenticado tiene un rol específico
     */
    protected function authorizeRole(string|array $roles): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user || !$user->hasAnyRole((array) $roles)) {
            abort(Response::HTTP_FORBIDDEN, 'No tienes autorización para acceder a este recurso.');
        }
    }

    /**
     * Verificar si el usuario autenticado tiene un permiso específico
     */
    protected function authorizePermission(string|array $permissions): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user || !$user->hasAnyPermission((array) $permissions)) {
            abort(Response::HTTP_FORBIDDEN, 'No tienes permiso para realizar esta acción.');
        }
    }

    /**
     * Verificar si el usuario tiene todos los permisos especificados
     */
    protected function authorizeAllPermissions(array $permissions): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user || !$user->hasAllPermissions($permissions)) {
            abort(Response::HTTP_FORBIDDEN, 'No tienes todos los permisos necesarios para realizar esta acción.');
        }
    }

    /**
     * Verificar rol y retornar respuesta JSON si falla
     */
    protected function checkRole(string|array $roles): ?JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user || !$user->hasAnyRole((array) $roles)) {
            return response()->json([
                'message' => 'No tienes autorización para acceder a este recurso.',
                'required_roles' => (array) $roles,
            ], Response::HTTP_FORBIDDEN);
        }

        return null;
    }

    /**
     * Verificar permiso y retornar respuesta JSON si falla
     */
    protected function checkPermission(string|array $permissions): ?JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user || !$user->hasAnyPermission((array) $permissions)) {
            return response()->json([
                'message' => 'No tienes permiso para realizar esta acción.',
                'required_permissions' => (array) $permissions,
            ], Response::HTTP_FORBIDDEN);
        }

        return null;
    }

    /**
     * Obtener el usuario autenticado
     */
    protected function currentUser(): ?User
    {
        /** @var User|null $user */
        $user = Auth::user();
        return $user;
    }

    /**
     * Verificar si el usuario es propietario
     */
    protected function isPropietario(): bool
    {
        return $this->currentUser()?->isPropietario() ?? false;
    }

    /**
     * Verificar si el usuario es vendedor
     */
    protected function isVendedor(): bool
    {
        return $this->currentUser()?->isVendedor() ?? false;
    }
}
