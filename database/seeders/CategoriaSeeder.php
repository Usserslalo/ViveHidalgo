<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = [
            [
                'name' => 'Pueblos Mágicos',
                'description' => 'Destinos con encanto y tradición',
                'slug' => 'pueblos-magicos',
                'icon' => '🏘️',
            ],
            [
                'name' => 'Aventura',
                'description' => 'Experiencias emocionantes y deportes extremos',
                'slug' => 'aventura',
                'icon' => '🏔️',
            ],
            [
                'name' => 'Cultura',
                'description' => 'Sitios históricos y culturales',
                'slug' => 'cultura',
                'icon' => '🏛️',
            ],
            [
                'name' => 'Naturaleza',
                'description' => 'Parques naturales y reservas ecológicas',
                'slug' => 'naturaleza',
                'icon' => '🌲',
            ],
            [
                'name' => 'Gastronomía',
                'description' => 'Restaurantes y experiencias culinarias',
                'slug' => 'gastronomia',
                'icon' => '🍽️',
            ],
        ];

        foreach ($categorias as $categoria) {
            Categoria::firstOrCreate(
                ['slug' => $categoria['slug']],
                $categoria
            );
        }
    }
} 