<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;

class AssignRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:assign-role
                            {email : El email del usuario}
                            {role : El nombre del rol (propietario, vendedor)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Asignar un rol a un usuario';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $roleName = $this->argument('role');

        // Buscar usuario
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("No se encontró ningún usuario con el email: {$email}");
            return Command::FAILURE;
        }

        // Buscar rol
        $role = Role::where('name', $roleName)->first();

        if (!$role) {
            $this->error("No se encontró ningún rol con el nombre: {$roleName}");
            $this->info('Roles disponibles:');
            Role::all()->each(function ($role) {
                $this->line("  - {$role->name} ({$role->display_name})");
            });
            return Command::FAILURE;
        }

        // Asignar rol
        $user->assignRole($role);

        $this->info("✓ Rol '{$role->display_name}' asignado exitosamente al usuario {$user->name} ({$user->email})");

        // Mostrar roles actuales del usuario
        $this->info("\nRoles actuales del usuario:");
        $user->roles->each(function ($role) {
            $this->line("  - {$role->display_name} ({$role->name})");
        });

        return Command::SUCCESS;
    }
}
