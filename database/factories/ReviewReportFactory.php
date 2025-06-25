<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\User;
use App\Models\ReviewReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReviewReport>
 */
class ReviewReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'review_id' => Review::factory(),
            'reporter_id' => User::factory(),
            'reason' => $this->faker->randomElement([
                'inappropriate_content',
                'spam',
                'fake_review',
                'harassment',
                'offensive_language',
                'other'
            ]),
            'status' => $this->faker->randomElement([
                ReviewReport::STATUS_PENDING,
                ReviewReport::STATUS_RESOLVED,
                ReviewReport::STATUS_DISMISSED
            ]),
            'admin_notes' => $this->faker->optional(0.3)->paragraph(),
            'resolved_by' => null,
            'resolved_at' => null,
        ];
    }

    /**
     * Indicate that the report is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReviewReport::STATUS_PENDING,
            'resolved_by' => null,
            'resolved_at' => null,
        ]);
    }

    /**
     * Indicate that the report is resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReviewReport::STATUS_RESOLVED,
            'resolved_by' => User::factory(),
            'resolved_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the report is dismissed.
     */
    public function dismissed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReviewReport::STATUS_DISMISSED,
            'resolved_by' => User::factory(),
            'resolved_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the report is for inappropriate content.
     */
    public function inappropriateContent(): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => 'inappropriate_content',
        ]);
    }

    /**
     * Indicate that the report is for spam.
     */
    public function spam(): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => 'spam',
        ]);
    }

    /**
     * Indicate that the report is for fake review.
     */
    public function fakeReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => 'fake_review',
        ]);
    }

    /**
     * Indicate that the report is for harassment.
     */
    public function harassment(): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => 'harassment',
        ]);
    }

    /**
     * Indicate that the report is for offensive language.
     */
    public function offensiveLanguage(): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => 'offensive_language',
        ]);
    }
} 