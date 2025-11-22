<?php

namespace Database\Seeders;

use App\Enum\RoleEnum;
use App\Models\Role;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Primero ejecutar el seeder de roles y permisos
        $this->call([
            RolePermissionSeeder::class,
        ]);

        // Crear usuario administrador con rol de Propietario
        $admin = User::create([
            'name' => 'Administrador',
            'apellido' => 'Sistema',
            'email' => 'admin@celestina.com',
            'password' => Hash::make('password'),
            'telefono' => '70000000',
            'domicilio' => 'Oficina Central',
            'email_verified_at' => now(),
        ]);

        // Asignar rol de Propietario (tiene todos los permisos)
        $propietarioRole = Role::where('name', RoleEnum::PROPIETARIO->value)->first();
        $admin->roles()->attach($propietarioRole->id);

        $this->command->info('Usuario administrador creado:');
        $this->command->info('Email: admin@celestina.com');
        $this->command->info('Password: password');
        $this->command->info('Rol: Propietario (Acceso total al sistema)');

        // Crear usuario de prueba con rol de Vendedor
        $vendedor = User::create([
            'name' => 'Vendedor',
            'apellido' => 'Demo',
            'email' => 'vendedor@celestina.com',
            'password' => Hash::make('password'),
            'telefono' => '70111111',
            'domicilio' => 'Av. Principal 123',
            'email_verified_at' => now(),
        ]);

        // Asignar rol de Vendedor
        $vendedorRole = Role::where('name', RoleEnum::VENDEDOR->value)->first();
        $vendedor->roles()->attach($vendedorRole->id);

        $this->command->info('Usuario vendedor creado:');
        $this->command->info('Email: vendedor@celestina.com');
        $this->command->info('Password: password');
        $this->command->info('Rol: Vendedor (Acceso limitado)');

        // Crear usuario de prueba con rol de Cliente
        $cliente = User::create([
            'name' => 'Juan',
            'email' => 'cliente@celestina.com',
            'password' => Hash::make('password'),
            'telefono' => '70222222',
            'domicilio' => 'Calle Comercio 456',
            'email_verified_at' => now(),
        ]);

        // Asignar rol de Cliente
        $clienteRole = Role::where('name', RoleEnum::CLIENTE->value)->first();
        $cliente->roles()->attach($clienteRole->id);

        $this->command->info('Usuario cliente creado:');
        $this->command->info('Email: cliente@celestina.com');
        $this->command->info('Password: password');
        $this->command->info('Rol: Cliente');

        // Crear 50 clientes adicionales y productos
        $this->call([
            //UsersSeeder::class,
            MenuItemSeeder::class,
           // ClienteSeeder::class,
            //CategoriaSeeder::class,
            //UnidadMedidaSeeder::class,
           // ProductoSeeder::class,
        ]);
    }
}
