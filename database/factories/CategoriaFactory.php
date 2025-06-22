<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Categoria>
 */
class CategoriaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categorias = [
            'Pueblo Mágico' => 'Localidades con atributos simbólicos, leyendas, historia, hechos trascendentes, cotidianidad.',
            'Sitio Arqueológico' => 'Lugares con vestigios de civilizaciones antiguas y gran valor histórico.',
            'Parque Natural' => 'Áreas protegidas con biodiversidad y paisajes naturales únicos.',
            'Museo' => 'Espacios culturales que preservan y exhiben el patrimonio histórico y artístico.',
            'Gastronomía Local' => 'Restaurantes y establecimientos con la mejor cocina tradicional.',
            'Aventura' => 'Actividades de ecoturismo y deportes extremos en entornos naturales.',
            'Artesanías' => 'Talleres y tiendas con productos artesanales únicos de la región.',
            'Termas y Balnearios' => 'Aguas termales y spas para relajación y bienestar.',
        ];

        $categoria = $this->faker->unique()->randomElement(array_keys($categorias));
        
        return [
            'name' => $categoria,
            'description' => $categorias[$categoria],
        ];
    }
} 