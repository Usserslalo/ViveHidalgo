<?php

namespace Database\Factories;

use App\Models\Destino;
use App\Models\Promocion;
use Illuminate\Database\Eloquent\Factories\Factory;

class PromocionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Promocion::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'destino_id' => Destino::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
            'code' => $this->faker->unique()->word,
            'discount_percentage' => $this->faker->numberBetween(10, 50),
            'start_date' => now(),
            'end_date' => now()->addDays(7),
            'is_active' => true,
        ];
    }
} 