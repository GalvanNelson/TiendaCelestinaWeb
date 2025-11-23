<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MenuItem;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        $menuItems = [
            // Dashboard
            [
                'name' => 'dashboard',
                'label' => 'Dashboard',
                'icon' => 'LayoutDashboard',
                'route' => 'dashboard',
                'permission' => null,
                'order' => 1,
            ],

            // Usuarios
            [
                'name' => 'users',
                'label' => 'Usuarios',
                'icon' => 'Users',
                'route' => 'users.index',
                'permission' => 'view users',
                'order' => 2,
            ],

            // Clientes
            [
                'name' => 'clientes',
                'label' => 'Clientes',
                'icon' => 'UserCheck',
                'route' => 'clientes.index',
                'permission' => 'view clients',
                'order' => 3,
            ],

            // Productos
            [
                'name' => 'productos',
                'label' => 'Productos',
                'icon' => 'Box',
                'route' => 'productos.index',
                'permission' => 'view products',
                'order' => 4,
            ],

            // Categorías
            [
                'name' => 'categorias',
                'label' => 'Categorías',
                'icon' => 'Tag',
                'route' => 'productos.categorias.index',
                'permission' => 'view categorias',
                'order' => 5,
            ],

            // Posts
           /* [
                'name' => 'posts',
                'label' => 'Posts',
                'icon' => 'FileText',
                'route' => 'posts.index',
                'permission' => 'view posts',
                'order' => 3,
            ],

            // Categorías
            [
                'name' => 'categories',
                'label' => 'Categorías',
                'icon' => 'FolderOpen',
                'route' => 'categories.index',
                'permission' => 'view categories',
                'order' => 4,
            ],

            // Comentarios
            [
                'name' => 'comments',
                'label' => 'Comentarios',
                'icon' => 'MessageSquare',
                'route' => 'comments.index',
                'permission' => 'view comments',
                'order' => 5,
            ],

            // Separador Administración
            [
                'name' => 'admin_separator',
                'label' => 'Administración',
                'is_separator' => true,
                'separator_label' => 'Administración',
                'permission' => 'view reports',
                'order' => 6,
            ],

            // Reportes
            [
                'name' => 'reports',
                'label' => 'Reportes',
                'icon' => 'BarChart3',
                'route' => 'reports.index',
                'permission' => 'view reports',
                'order' => 7,
            ],

            // Roles y Permisos
            [
                'name' => 'roles',
                'label' => 'Roles y Permisos',
                'icon' => 'Shield',
                'route' => 'roles.index',
                'permission' => 'view roles',
                'order' => 8,
            ],*/

            // Configuración
            [
                'name' => 'settings',
                'label' => 'Configuración',
                'icon' => 'Settings',
                'route' => 'settings.index',
                'permission' => 'view settings',
                'order' => 9,
            ],
        ];

        foreach ($menuItems as $item) {
            MenuItem::create($item);
        }

        $this->command->info('Menús creados exitosamente!');
    }
}
