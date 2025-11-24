<?php

namespace Database\Seeders;

use App\Models\UnidadMedida;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnidadMedidaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $unidades = [
            ['nombre' => 'Unidad'],
            ['nombre' => 'kg'],
            ['nombre' => 'gr'],
            ['nombre' => 'Lts'],
            ['nombre' => 'ml'],
        ];

        foreach ($unidades as $unidad) {
            UnidadMedida::create($unidad);
        }

        $this->command->info('Unidades de medida creadas exitosamente.');
    }
}
