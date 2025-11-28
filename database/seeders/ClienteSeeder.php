<?php

namespace Database\Seeders;

use App\Enum\RoleEnum;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ClienteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clienteRole = Role::where('name', RoleEnum::CLIENTE->value)->first();

        if (!$clienteRole) {
            $this->command->error('El rol Cliente no existe. Ejecuta primero RolePermissionSeeder.');
            return;
        }

        $nombres = [
            'Juan', 'María', 'Pedro', 'Ana', 'Carlos', 'Lucía', 'Diego', 'Sofia', 'Miguel', 'Laura',
            'José', 'Carmen', 'Luis', 'Elena', 'Antonio', 'Isabel', 'Francisco', 'Rosa', 'Manuel', 'Patricia',
            'Javier', 'Marta', 'Roberto', 'Andrea', 'Fernando', 'Beatriz', 'Ricardo', 'Clara', 'Sergio', 'Natalia',
            'Alberto', 'Silvia', 'Raúl', 'Gabriela', 'Pablo', 'Daniela', 'Andrés', 'Valentina', 'Marcos', 'Lorena',
            'Jorge', 'Cecilia', 'Héctor', 'Alejandra', 'Víctor', 'Marina', 'Daniel', 'Cristina', 'Álvaro', 'Sandra'
        ];

        $apellidos = [
            'García', 'Rodríguez', 'Martínez', 'López', 'González', 'Pérez', 'Sánchez', 'Ramírez', 'Torres', 'Flores',
            'Rivera', 'Gómez', 'Díaz', 'Cruz', 'Morales', 'Reyes', 'Gutiérrez', 'Ortiz', 'Chávez', 'Ruiz',
            'Jiménez', 'Hernández', 'Mendoza', 'Álvarez', 'Castillo', 'Romero', 'Vargas', 'Silva', 'Castro', 'Ramos',
            'Vega', 'Medina', 'Aguilar', 'Guerrero', 'Moreno', 'Delgado', 'Campos', 'Núñez', 'Valdez', 'Cortés',
            'Espinoza', 'Navarro', 'Paredes', 'Ríos', 'Salazar', 'Cabrera', 'Peña', 'Santos', 'Figueroa', 'León'
        ];

        $calles = [
            'Av. Principal', 'Calle Comercio', 'Av. Libertador', 'Calle Central', 'Av. América',
            'Calle Bolívar', 'Av. Estudiantes', 'Calle Sucre', 'Av. Universidad', 'Calle 21 de Mayo',
            'Av. Monseñor Rivero', 'Calle Junín', 'Av. Cristo Redentor', 'Calle Potosí', 'Av. Circunvalación'
        ];

        $this->command->info('Creando 50 clientes...');

        for ($i = 0; $i < 20; $i++) {
            $nombre = $nombres[$i];
            $apellido = $apellidos[$i];
            $email = strtolower(str_replace(' ', '', $nombre)) . '.' . strtolower($apellido) . '@ejemplo.com';

            $cliente = User::create([
                'name' => $nombre,
                'apellido' => $apellido,
                'email' => $email,
                'password' => Hash::make('password'),
                'telefono' => '7' . rand(0, 9) . rand(100000, 999999),
                'domicilio' => $calles[array_rand($calles)] . ' #' . rand(100, 999),
                'email_verified_at' => now(),
            ]);

            // Asignar rol de Cliente
            $cliente->roles()->attach($clienteRole->id);
        }

        $this->command->info('20 clientes creados exitosamente.');
    }
}
