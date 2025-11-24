<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = [
            ['nombre' => 'Bebidas'],
            ['nombre' => 'Granos y Cereales'],
            ['nombre' => 'Harinas'],
            ['nombre' => 'Panadería'],
            ['nombre' => 'Aceites'],
            ['nombre' => 'Lácteos'],
            ['nombre' => 'Conservas'],
            ['nombre' => 'Condimentos'],
            ['nombre' => 'Especias'],
            ['nombre' => 'Snacks'],
        ];
        foreach ($categorias as $categoria) {
            Categoria::create($categoria);
        }

        $this->command->info('Categorías creadas exitosamente.');
    }
}
