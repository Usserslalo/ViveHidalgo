<?php

namespace Database\Factories;

use App\Models\Destino;
use App\Models\Imagen;
use App\Models\Promocion;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Imagen>
 */
class ImagenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'path' => 'images/placeholder.jpg',
            'alt' => $this->faker->sentence(3),
            'orden' => $this->faker->numberBetween(0, 10),
            'is_main' => false,
            'disk' => 'public',
            'mime_type' => 'image/jpeg',
            'size' => $this->faker->numberBetween(100000, 2000000), // 100KB - 2MB
        ];
    }

    /**
     * Indicate that the image is the main image.
     */
    public function main(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_main' => true,
            'orden' => 0,
        ]);
    }

    /**
     * Indicate that the image belongs to a destino.
     */
    public function forDestino(Destino $destino): static
    {
        return $this->state(fn (array $attributes) => [
            'imageable_type' => Destino::class,
            'imageable_id' => $destino->id,
        ]);
    }

    /**
     * Indicate that the image belongs to a promocion.
     */
    public function forPromocion(Promocion $promocion): static
    {
        return $this->state(fn (array $attributes) => [
            'imageable_type' => Promocion::class,
            'imageable_id' => $promocion->id,
        ]);
    }

    /**
     * Indicate that the image belongs to a region.
     */
    public function forRegion(Region $region): static
    {
        return $this->state(fn (array $attributes) => [
            'imageable_type' => Region::class,
            'imageable_id' => $region->id,
        ]);
    }
} 