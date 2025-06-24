<?php

namespace Database\Factories;

use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Destino>
 */
class DestinoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->city() . ' ' . $this->faker->randomElement(['Turístico', 'Mágico', 'Histórico', 'Natural']);
        
        return [
            'user_id' => User::factory(),
            'region_id' => Region::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'short_description' => $this->faker->sentence(10),
            'description' => $this->faker->paragraphs(3, true),
            'status' => $this->faker->randomElement(['draft', 'pending_review', 'published']),
            'address' => $this->faker->address(),
            'latitude' => $this->faker->latitude(19.0, 21.0), // Coordenadas aproximadas de Hidalgo
            'longitude' => $this->faker->longitude(-99.5, -97.5), // Coordenadas aproximadas de Hidalgo
            'phone' => $this->faker->phoneNumber(),
            'whatsapp' => $this->faker->phoneNumber(),
            'email' => $this->faker->email(),
            'website' => $this->faker->url(),
            'is_featured' => $this->faker->boolean(20), // 20% de probabilidad de ser destacado
            'is_top' => $this->faker->boolean(10), // 10% de probabilidad de ser TOP
        ];
    }

    /**
     * Indicate that the destination is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
        ]);
    }

    /**
     * Indicate that the destination is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * Indicate that the destination is pending review.
     */
    public function pendingReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending_review',
        ]);
    }

    /**
     * Indicate that the destination is a top destination.
     */
    public function top(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_top' => true,
        ]);
    }
} 