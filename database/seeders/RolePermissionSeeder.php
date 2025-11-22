<?php

namespace Database\Seeders;

use App\Enum\PermissionEnum;
use App\Enum\RoleEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear todos los permisos
        $permissions = [];
        foreach (PermissionEnum::cases() as $permissionEnum) {
            $permissions[$permissionEnum->value] = Permission::create([
                'name' => $permissionEnum->value,
                'display_name' => $permissionEnum->label(),
                'description' => $permissionEnum->description(),
            ]);
        }

        // Crear rol Propietario con todos los permisos
        $propietarioRole = Role::create([
            'name' => RoleEnum::PROPIETARIO->value,
            'display_name' => 'Propietario',
            'description' => 'Acceso completo al sistema',
        ]);

        // Asignar todos los permisos al propietario
        $propietarioRole->permissions()->sync(array_column($permissions, 'id'));

        // Crear rol Vendedor con permisos limitados
        $vendedorRole = Role::create([
            'name' => RoleEnum::VENDEDOR->value,
            'display_name' => 'Vendedor',
            'description' => 'Acceso limitado para operaciones de venta',
        ]);

        // Permisos del Vendedor
        $vendedorPermissions = [
            PermissionEnum::VIEW_CLIENTS->value,
            PermissionEnum::CREATE_CLIENTS->value,
            PermissionEnum::EDIT_CLIENTS->value,

            PermissionEnum::VIEW_PRODUCTS->value,

            PermissionEnum::VIEW_CATEGORIAS->value,

            PermissionEnum::VIEW_SALES->value,
            PermissionEnum::CREATE_SALES->value,
            PermissionEnum::EDIT_SALES->value,

            PermissionEnum::VIEW_REPORTS->value,
        ];

        $vendedorPermissionIds = collect($vendedorPermissions)
            ->map(fn($permission) => $permissions[$permission]->id)
            ->toArray();

        $vendedorRole->permissions()->sync($vendedorPermissionIds);

        // Crear rol Cliente con permisos básicos
        $clienteRole = Role::create([
            'name' => RoleEnum::CLIENTE->value,
            'display_name' => 'Cliente',
            'description' => 'Usuario cliente del sistema con permisos básicos',
        ]);

        // Los clientes no tienen permisos administrativos
        // Solo pueden ver sus propios datos y realizar compras
        $clientePermissions = [
            // Por ahora sin permisos administrativos
            // Se pueden agregar permisos específicos de cliente más adelante
        ];

        if (!empty($clientePermissions)) {
            $clientePermissionIds = collect($clientePermissions)
                ->map(fn($permission) => $permissions[$permission]->id)
                ->toArray();
            $clienteRole->permissions()->sync($clientePermissionIds);
        }

        $this->command->info('Roles y permisos creados exitosamente.');
        $this->command->info('- Rol Propietario: ' . count($permissions) . ' permisos');
        $this->command->info('- Rol Vendedor: ' . count($vendedorPermissions) . ' permisos');
        $this->command->info('- Rol Cliente: ' . count($clientePermissions) . ' permisos');
    }
}
