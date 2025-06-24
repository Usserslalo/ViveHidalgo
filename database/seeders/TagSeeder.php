<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            ['name' => 'Romántico', 'color' => '#FF69B4', 'description' => 'Destinos perfectos para parejas'],
            ['name' => 'Familiar', 'color' => '#32CD32', 'description' => 'Ideal para viajes con niños'],
            ['name' => 'Económico', 'color' => '#FFD700', 'description' => 'Opciones accesibles para todos los presupuestos'],
            ['name' => 'Aventura', 'color' => '#FF4500', 'description' => 'Experiencias emocionantes y desafiantes'],
            ['name' => 'Cultura', 'color' => '#8A2BE2', 'description' => 'Rica historia y tradiciones locales'],
            ['name' => 'Naturaleza', 'color' => '#228B22', 'description' => 'Entornos naturales y vida silvestre'],
            ['name' => 'Histórico', 'color' => '#8B4513', 'description' => 'Sitios con gran valor histórico'],
            ['name' => 'Gastronomía', 'color' => '#FF6347', 'description' => 'Experiencias culinarias únicas'],
            ['name' => 'Relax', 'color' => '#87CEEB', 'description' => 'Destinos para descansar y relajarse'],
            ['name' => 'Deportes', 'color' => '#FF8C00', 'description' => 'Actividades deportivas y recreativas'],
            ['name' => 'Fotografía', 'color' => '#9932CC', 'description' => 'Lugares perfectos para capturar momentos'],
            ['name' => 'Arte', 'color' => '#FF1493', 'description' => 'Expresiones artísticas y creativas'],
            ['name' => 'Música', 'color' => '#00CED1', 'description' => 'Festivales y eventos musicales'],
            ['name' => 'Festival', 'color' => '#FF69B4', 'description' => 'Celebraciones y eventos especiales'],
            ['name' => 'Tradicional', 'color' => '#CD853F', 'description' => 'Costumbres y tradiciones locales'],
            ['name' => 'Moderno', 'color' => '#4169E1', 'description' => 'Destinos con infraestructura moderna'],
            ['name' => 'Rural', 'color' => '#228B22', 'description' => 'Experiencias en entornos rurales'],
            ['name' => 'Urbano', 'color' => '#696969', 'description' => 'Vida de ciudad y entretenimiento urbano'],
            ['name' => 'Montaña', 'color' => '#8B4513', 'description' => 'Destinos en zonas montañosas'],
            ['name' => 'Bosque', 'color' => '#228B22', 'description' => 'Áreas boscosas y senderos naturales'],
            ['name' => 'Lago', 'color' => '#4682B4', 'description' => 'Actividades acuáticas en lagos'],
            ['name' => 'Río', 'color' => '#1E90FF', 'description' => 'Aventuras en ríos y cascadas'],
            ['name' => 'Cascada', 'color' => '#00BFFF', 'description' => 'Hermosas cascadas y saltos de agua'],
        ];

        foreach ($tags as $tag) {
            Tag::create($tag);
        }

        $this->command->info(count($tags) . ' tags creados exitosamente.');
    }
} 