<?php

namespace Database\Factories;

use App\Models\PromocionDestacada;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PromocionDestacada>
 */
class PromocionDestacadaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PromocionDestacada::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fechaInicio = $this->faker->dateTimeBetween('now', '+1 month');
        $fechaFin = $this->faker->dateTimeBetween($fechaInicio, '+2 months');

        return [
            'titulo' => $this->faker->sentence(3),
            'descripcion' => $this->faker->paragraph(3),
            'imagen' => 'https://via.placeholder.com/800x400/4F46E5/FFFFFF?text=Promoción+Destacada',
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'is_active' => $this->faker->boolean(80), // 80% de probabilidad de estar activa
        ];
    }

    /**
     * Indicate that the promotion is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the promotion is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the promotion is currently active (vigente).
     */
    public function vigente(): static
    {
        return $this->state(function (array $attributes) {
            $fechaInicio = now()->subDays(rand(1, 10));
            $fechaFin = now()->addDays(rand(1, 20));

            return [
                'is_active' => true,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
            ];
        });
    }

    /**
     * Indicate that the promotion is future.
     */
    public function futura(): static
    {
        return $this->state(function (array $attributes) {
            $fechaInicio = now()->addDays(rand(1, 30));
            $fechaFin = now()->addDays(rand(31, 60));

            return [
                'is_active' => true,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
            ];
        });
    }

    /**
     * Indicate that the promotion is expired.
     */
    public function expirada(): static
    {
        return $this->state(function (array $attributes) {
            $fechaInicio = now()->subDays(rand(30, 60));
            $fechaFin = now()->subDays(rand(1, 29));

            return [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
            ];
        });
    }
} 