<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\User;
use App\Models\Destino;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'destino_id' => Destino::factory(),
            'rating' => $this->faker->numberBetween(1, 5),
            'comment' => $this->faker->sentence(10),
            'is_approved' => false, // false = pendiente/rechazado, true = aprobado
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function approved()
    {
        return $this->state([
            'is_approved' => true,
        ]);
    }

    public function rejected()
    {
        return $this->state([
            'is_approved' => false,
        ]);
    }

    public function pending()
    {
        return $this->state([
            'is_approved' => false, // En este sistema, false = pendiente
        ]);
    }
} 