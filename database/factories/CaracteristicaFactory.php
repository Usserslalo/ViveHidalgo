<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Caracteristica>
 */
class CaracteristicaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $caracteristicas = [
            // Amenidades básicas
            'WiFi' => ['tipo' => 'amenidad', 'icono' => 'fas fa-wifi'],
            'Estacionamiento' => ['tipo' => 'amenidad', 'icono' => 'fas fa-parking'],
            'Aire acondicionado' => ['tipo' => 'amenidad', 'icono' => 'fas fa-snowflake'],
            'Restaurante' => ['tipo' => 'amenidad', 'icono' => 'fas fa-utensils'],
            'Bar' => ['tipo' => 'amenidad', 'icono' => 'fas fa-glass-martini'],
            'Piscina' => ['tipo' => 'amenidad', 'icono' => 'fas fa-swimming-pool'],
            'Gimnasio' => ['tipo' => 'amenidad', 'icono' => 'fas fa-dumbbell'],
            'Spa' => ['tipo' => 'amenidad', 'icono' => 'fas fa-spa'],
            
            // Características especiales
            'Pet-friendly' => ['tipo' => 'especial', 'icono' => 'fas fa-paw'],
            'Accesible' => ['tipo' => 'especial', 'icono' => 'fas fa-wheelchair'],
            'Familiar' => ['tipo' => 'especial', 'icono' => 'fas fa-baby'],
            'Romántico' => ['tipo' => 'especial', 'icono' => 'fas fa-heart'],
            'Ecológico' => ['tipo' => 'especial', 'icono' => 'fas fa-leaf'],
            
            // Tipos de alojamiento
            'Cabañas' => ['tipo' => 'alojamiento', 'icono' => 'fas fa-home'],
            'Hotel' => ['tipo' => 'alojamiento', 'icono' => 'fas fa-hotel'],
            'Hostal' => ['tipo' => 'alojamiento', 'icono' => 'fas fa-bed'],
            'Camping' => ['tipo' => 'alojamiento', 'icono' => 'fas fa-campground'],
            
            // Actividades
            'Senderismo' => ['tipo' => 'actividad', 'icono' => 'fas fa-hiking'],
            'Ciclismo' => ['tipo' => 'actividad', 'icono' => 'fas fa-bicycle'],
            'Pesca' => ['tipo' => 'actividad', 'icono' => 'fas fa-fish'],
            'Escalada' => ['tipo' => 'actividad', 'icono' => 'fas fa-mountain'],
            'Rappel' => ['tipo' => 'actividad', 'icono' => 'fas fa-arrow-down'],
            
            // Características culturales
            'Pueblo Mágico' => ['tipo' => 'cultural', 'icono' => 'fas fa-star'],
            'Sitio histórico' => ['tipo' => 'cultural', 'icono' => 'fas fa-landmark'],
            'Museo' => ['tipo' => 'cultural', 'icono' => 'fas fa-museum'],
            'Artesanías' => ['tipo' => 'cultural', 'icono' => 'fas fa-palette'],
            
            // Características naturales
            'Cascada' => ['tipo' => 'natural', 'icono' => 'fas fa-water'],
            'Bosque' => ['tipo' => 'natural', 'icono' => 'fas fa-tree'],
            'Río' => ['tipo' => 'natural', 'icono' => 'fas fa-water'],
            'Mirador' => ['tipo' => 'natural', 'icono' => 'fas fa-binoculars'],
        ];

        $caracteristica = $this->faker->unique()->randomElement(array_keys($caracteristicas));
        $datos = $caracteristicas[$caracteristica];
        
        return [
            'nombre' => $caracteristica,
            'slug' => Str::slug($caracteristica),
            'tipo' => $datos['tipo'],
            'icono' => $datos['icono'],
            'descripcion' => $this->faker->sentence(10),
            'activo' => true,
        ];
    }

    /**
     * Indicate that the characteristic is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }

    /**
     * Indicate that the characteristic is an amenity.
     */
    public function amenidad(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'amenidad',
        ]);
    }

    /**
     * Indicate that the characteristic is an activity.
     */
    public function actividad(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'actividad',
        ]);
    }

    /**
     * Indicate that the characteristic is cultural.
     */
    public function cultural(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 'cultural',
        ]);
    }
} 