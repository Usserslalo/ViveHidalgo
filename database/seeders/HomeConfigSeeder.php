<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HomeConfig;

class HomeConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear configuración activa del home
        HomeConfig::create([
            'hero_title' => 'Descubre Hidalgo',
            'hero_subtitle' => 'Tierra de aventura y tradición',
            'search_placeholder' => 'Busca destinos, actividades, experiencias...',
            'featured_sections' => [
                [
                    'slug' => 'pueblos-magicos',
                    'title' => 'Pueblos Mágicos',
                    'subtitle' => 'Descubre la magia de nuestros pueblos',
                    'image' => 'https://via.placeholder.com/800x600/8B5CF6/FFFFFF?text=Pueblos+Mágicos',
                    'destinations_count' => 8,
                    'order' => 1
                ],
                [
                    'slug' => 'aventura',
                    'title' => 'Aventura',
                    'subtitle' => 'Experiencias llenas de adrenalina',
                    'image' => 'https://via.placeholder.com/800x600/10B981/FFFFFF?text=Aventura',
                    'destinations_count' => 12,
                    'order' => 2
                ],
                [
                    'slug' => 'cultura',
                    'title' => 'Cultura',
                    'subtitle' => 'Historia y tradiciones vivas',
                    'image' => 'https://via.placeholder.com/800x600/F59E0B/FFFFFF?text=Cultura',
                    'destinations_count' => 15,
                    'order' => 3
                ],
                [
                    'slug' => 'gastronomia',
                    'title' => 'Gastronomía',
                    'subtitle' => 'Sabores únicos de Hidalgo',
                    'image' => 'https://via.placeholder.com/800x600/EF4444/FFFFFF?text=Gastronomía',
                    'destinations_count' => 20,
                    'order' => 4
                ],
                [
                    'slug' => 'naturaleza',
                    'title' => 'Naturaleza',
                    'subtitle' => 'Paisajes que te dejarán sin aliento',
                    'image' => 'https://via.placeholder.com/800x600/059669/FFFFFF?text=Naturaleza',
                    'destinations_count' => 18,
                    'order' => 5
                ]
            ],
            'is_active' => true
        ]);

        $this->command->info('Configuración del home creada exitosamente.');
    }
} 