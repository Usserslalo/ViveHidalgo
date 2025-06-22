<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Region>
 */
class RegionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $regions = [
            'Comarca Minera' => 'Una región rica en historia minera y paisajes únicos.',
            'Sierra Gorda' => 'Montañas imponentes y naturaleza virgen.',
            'Valle del Mezquital' => 'Tierra de contrastes y tradiciones ancestrales.',
            'Huasteca Hidalguense' => 'Cultura viva y gastronomía excepcional.',
            'Altiplano' => 'Paisajes desérticos y cielos infinitos.',
        ];

        $region = $this->faker->unique()->randomElement(array_keys($regions));
        
        return [
            'name' => $region,
            'description' => $regions[$region],
        ];
    }
} 