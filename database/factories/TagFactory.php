<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tag>
 */
class TagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement([
            'Romántico', 'Familiar', 'Económico', 'Aventura', 'Cultura', 'Naturaleza',
            'Histórico', 'Gastronomía', 'Relax', 'Deportes', 'Fotografía', 'Arte',
            'Música', 'Festival', 'Tradicional', 'Moderno', 'Rural', 'Urbano',
            'Montaña', 'Playa', 'Bosque', 'Desierto', 'Lago', 'Río', 'Cascada'
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'color' => $this->faker->hexColor(),
            'description' => $this->faker->sentence(10),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the tag is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the tag has a specific color.
     */
    public function withColor(string $color): static
    {
        return $this->state(fn (array $attributes) => [
            'color' => $color,
        ]);
    }
} 