<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = [
            Invoice::STATUS_DRAFT,
            Invoice::STATUS_OPEN,
            Invoice::STATUS_PAID,
            Invoice::STATUS_VOID,
            Invoice::STATUS_UNCOLLECTIBLE,
        ];

        $currencies = [
            Invoice::CURRENCY_MXN,
            Invoice::CURRENCY_USD,
        ];

        $amount = $this->faker->randomFloat(2, 100, 2000);
        $status = $this->faker->randomElement($statuses);
        $currency = $this->faker->randomElement($currencies);
        $dueDate = $this->faker->dateTimeBetween('now', '+30 days');
        $paidAt = $status === Invoice::STATUS_PAID ? $this->faker->dateTimeBetween('-30 days', 'now') : null;

        return [
            'user_id' => User::factory(),
            'subscription_id' => null,
            'stripe_invoice_id' => 'in_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
            'amount' => $amount,
            'currency' => $currency,
            'status' => $status,
            'due_date' => $dueDate,
            'paid_at' => $paidAt,
            'metadata' => [
                'plan_type' => $this->faker->randomElement(['basic', 'premium', 'enterprise']),
                'billing_cycle' => $this->faker->randomElement(['monthly', 'quarterly', 'yearly']),
                'session_id' => 'cs_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
            ],
        ];
    }

    /**
     * Indicate that the invoice is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_PAID,
            'paid_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Indicate that the invoice is unpaid.
     */
    public function unpaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $this->faker->randomElement([Invoice::STATUS_DRAFT, Invoice::STATUS_OPEN]),
            'paid_at' => null,
        ]);
    }

    /**
     * Indicate that the invoice is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $this->faker->randomElement([Invoice::STATUS_OPEN, Invoice::STATUS_DRAFT]),
            'due_date' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
            'paid_at' => null,
        ]);
    }

    /**
     * Indicate that the invoice is for a specific plan.
     */
    public function forPlan(string $planType): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'plan_type' => $planType,
            ]),
        ]);
    }

    /**
     * Indicate that the invoice is for a specific billing cycle.
     */
    public function forBillingCycle(string $billingCycle): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'billing_cycle' => $billingCycle,
            ]),
        ]);
    }
} 