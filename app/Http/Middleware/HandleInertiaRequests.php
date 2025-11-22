<?php

namespace App\Http\Middleware;

use App\Enum\PermissionEnum as EnumPermissionEnum;
use Illuminate\Foundation\Inspiring;
use App\Enums\PermissionEnum;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
                'permissions' => $request->user()?->getAllPermissions() ?? [],
            ],
            'menuItems' => $this->getMenuItems($request),
        ];
    }

    /**
     * Obtener los items del menú dinámicamente
     */
    protected function getMenuItems(): array
    {
        return [
            [
                'id' => 'dashboard',
                'label' => 'Dashboard',
                'icon' => 'LayoutDashboard',
                'route' => 'dashboard',
                'permission' => null,
            ],
            // AGREGAR CLIENTES AQUÍ
            [
                'id' => 'clientes',
                'label' => 'Clientes',
                'icon' => 'Users',
                'route' => 'clientes.index',
                'permission' => EnumPermissionEnum::VIEW_CLIENTS->value, // o 'view_clients'
            ],
            [
                'id' => 'users',
                'label' => 'Usuarios',
                'icon' => 'UserCog',
                'route' => 'users.index',
                'permission' => null,
            ],
            [
                'id' => 'settings',
                'label' => 'Configuración',
                'icon' => 'Settings',
                'route' => 'settings.index',
                'permission' => null,
            ],
        ];
    }
}
