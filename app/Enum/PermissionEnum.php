<?php

namespace App\Enum;

enum PermissionEnum: string
{
    // Gestión de Usuarios
    case VIEW_USERS = 'view_users';
    case CREATE_USERS = 'create_users';
    case EDIT_USERS = 'edit_users';
    case DELETE_USERS = 'delete_users';

    // Gestión de Clientes
    case VIEW_CLIENTS = 'view_clients';
    case CREATE_CLIENTS = 'create_clients';
    case EDIT_CLIENTS = 'edit_clients';
    case DELETE_CLIENTS = 'delete_clients';

    // Gestión de Productos
    case VIEW_PRODUCTS = 'view_products';
    case CREATE_PRODUCTS = 'create_products';
    case EDIT_PRODUCTS = 'edit_products';
    case DELETE_PRODUCTS = 'delete_products';

    // Gestión de Ventas
    case VIEW_SALES = 'view_sales';
    case CREATE_SALES = 'create_sales';
    case EDIT_SALES = 'edit_sales';
    case DELETE_SALES = 'delete_sales';

    // Gestion de compras
    case VIEW_PURCHASES = 'view_purchases';
    case CREATE_PURCHASES = 'create_purchases';
    case EDIT_PURCHASES = 'edit_purchases';
    case DELETE_PURCHASES = 'delete_purchases';

    // Gestión de Reportes
    case VIEW_REPORTS = 'view_reports';
    case EXPORT_REPORTS = 'export_reports';

    // Gestión de Configuración
    case MANAGE_SETTINGS = 'manage_settings';
    case MANAGE_ROLES = 'manage_roles';
    case MANAGE_PERMISSIONS = 'manage_permissions';

    // Categorias
    case VIEW_CATEGORIAS = 'view_categorias';
    case CREATE_CATEGORIAS = 'create_categorias';
    case EDIT_CATEGORIAS = 'edit_categorias';
    case DELETE_CATEGORIAS = 'delete_categorias';

    // Unidades de Medida
    case VIEW_UNIDADES = 'view_unidades';
    case CREATE_UNIDADES = 'create_unidades';
    case EDIT_UNIDADES = 'edit_unidades';
    case DELETE_UNIDADES = 'delete_unidades';

    // Entradas de Stock
    case VIEW_ENTRADAS_STOCK = 'view_entradas_stock';
    case CREATE_ENTRADAS_STOCK = 'create_entradas_stock';
    case EDIT_ENTRADAS_STOCK = 'edit_entradas_stock';
    case DELETE_ENTRADAS_STOCK = 'delete_entradas_stock';

    // Salidas de Stock
    case VIEW_SALIDAS_STOCK = 'view_salidas_stock';
    case CREATE_SALIDAS_STOCK = 'create_salidas_stock';
    case EDIT_SALIDAS_STOCK = 'edit_salidas_stock';
    case DELETE_SALIDAS_STOCK = 'delete_salidas_stock';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match($this) {
            self::VIEW_USERS => 'Ver Usuarios',
            self::CREATE_USERS => 'Crear Usuarios',
            self::EDIT_USERS => 'Editar Usuarios',
            self::DELETE_USERS => 'Eliminar Usuarios',

            self::VIEW_CLIENTS => 'Ver Clientes',
            self::CREATE_CLIENTS => 'Crear Clientes',
            self::EDIT_CLIENTS => 'Editar Clientes',
            self::DELETE_CLIENTS => 'Eliminar Clientes',

            self::VIEW_PRODUCTS => 'Ver Productos',
            self::CREATE_PRODUCTS => 'Crear Productos',
            self::EDIT_PRODUCTS => 'Editar Productos',
            self::DELETE_PRODUCTS => 'Eliminar Productos',

            self::VIEW_SALES => 'Ver Ventas',
            self::CREATE_SALES => 'Crear Ventas',
            self::EDIT_SALES => 'Editar Ventas',
            self::DELETE_SALES => 'Eliminar Ventas',

            self::VIEW_PURCHASES => 'Ver Compras',
            self::CREATE_PURCHASES => 'Crear Compras',
            self::EDIT_PURCHASES => 'Editar Compras',
            self::DELETE_PURCHASES => 'Eliminar Compras',

            self::VIEW_REPORTS => 'Ver Reportes',
            self::EXPORT_REPORTS => 'Exportar Reportes',

            self::MANAGE_SETTINGS => 'Gestionar Configuración',
            self::MANAGE_ROLES => 'Gestionar Roles',
            self::MANAGE_PERMISSIONS => 'Gestionar Permisos',

            self::VIEW_CATEGORIAS => 'Ver Categorias',
            self::CREATE_CATEGORIAS => 'Crear Categorias',
            self::EDIT_CATEGORIAS => 'Editar Categorias',
            self::DELETE_CATEGORIAS => 'Eliminar Categorias',

            self::VIEW_UNIDADES => 'Ver Unidades de Medida',
            self::CREATE_UNIDADES => 'Crear Unidades de Medida',
            self::EDIT_UNIDADES => 'Editar Unidades de Medida',
            self::DELETE_UNIDADES => 'Eliminar Unidades de Medida',

            self::VIEW_ENTRADAS_STOCK => 'Ver Entradas de Stock',
            self::CREATE_ENTRADAS_STOCK => 'Crear Entradas de Stock',
            self::EDIT_ENTRADAS_STOCK => 'Editar Entradas de Stock',
            self::DELETE_ENTRADAS_STOCK => 'Eliminar Entradas de Stock',

            self::VIEW_SALIDAS_STOCK => 'Ver Salidas de Stock',
            self::CREATE_SALIDAS_STOCK => 'Crear Salidas de Stock',
            self::EDIT_SALIDAS_STOCK => 'Editar Salidas de Stock',
            self::DELETE_SALIDAS_STOCK => 'Eliminar Salidas de Stock',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::VIEW_USERS => 'Permite ver la lista de usuarios',
            self::CREATE_USERS => 'Permite crear nuevos usuarios',
            self::EDIT_USERS => 'Permite editar usuarios existentes',
            self::DELETE_USERS => 'Permite eliminar usuarios',

            self::VIEW_CLIENTS => 'Permite ver la lista de clientes',
            self::CREATE_CLIENTS => 'Permite crear nuevos clientes',
            self::EDIT_CLIENTS => 'Permite editar clientes existentes',
            self::DELETE_CLIENTS => 'Permite eliminar clientes',

            self::VIEW_PRODUCTS => 'Permite ver la lista de productos',
            self::CREATE_PRODUCTS => 'Permite crear nuevos productos',
            self::EDIT_PRODUCTS => 'Permite editar productos existentes',
            self::DELETE_PRODUCTS => 'Permite eliminar productos',

            self::VIEW_SALES => 'Permite ver la lista de ventas',
            self::CREATE_SALES => 'Permite realizar ventas',
            self::EDIT_SALES => 'Permite editar ventas existentes',
            self::DELETE_SALES => 'Permite eliminar ventas',

            self::VIEW_PURCHASES => 'Permite ver la lista de compras',
            self::CREATE_PURCHASES => 'Permite registrar nuevas compras',
            self::EDIT_PURCHASES => 'Permite editar compras existentes',
            self::DELETE_PURCHASES => 'Permite eliminar compras',

            self::VIEW_REPORTS => 'Permite ver reportes del sistema',
            self::EXPORT_REPORTS => 'Permite exportar reportes',

            self::MANAGE_SETTINGS => 'Permite gestionar la configuración del sistema',
            self::MANAGE_ROLES => 'Permite gestionar roles de usuario',
            self::MANAGE_PERMISSIONS => 'Permite gestionar permisos',

            self::VIEW_CATEGORIAS => 'Permite ver la lista de categorias',
            self::CREATE_CATEGORIAS => 'Permite crear nuevas categorias',
            self::EDIT_CATEGORIAS => 'Permite editar categorias existentes',
            self::DELETE_CATEGORIAS => 'Permite eliminar categorias',

            self::VIEW_UNIDADES => 'Permite ver la lista de unidades de medida',
            self::CREATE_UNIDADES => 'Permite crear nuevas unidades de medida',
            self::EDIT_UNIDADES => 'Permite editar unidades de medida existentes',
            self::DELETE_UNIDADES => 'Permite eliminar unidades de medida',

            self::VIEW_ENTRADAS_STOCK => 'Permite ver el historial de entradas de stock',
            self::CREATE_ENTRADAS_STOCK => 'Permite registrar nuevas entradas de stock',
            self::EDIT_ENTRADAS_STOCK => 'Permite editar entradas de stock existentes',
            self::DELETE_ENTRADAS_STOCK => 'Permite eliminar entradas de stock',

            self::VIEW_SALIDAS_STOCK => 'Permite ver el historial de salidas de stock',
            self::CREATE_SALIDAS_STOCK => 'Permite registrar nuevas salidas de stock',
            self::EDIT_SALIDAS_STOCK => 'Permite editar salidas de stock existentes',
            self::DELETE_SALIDAS_STOCK => 'Permite eliminar salidas de stock',
        };
    }
}
