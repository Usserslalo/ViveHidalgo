<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $planType = $this->faker->randomElement([
            Subscription::PLAN_BASIC,
            Subscription::PLAN_PREMIUM,
            Subscription::PLAN_ENTERPRISE
        ]);

        $billingCycle = $this->faker->randomElement([
            Subscription::CYCLE_MONTHLY,
            Subscription::CYCLE_QUARTERLY,
            Subscription::CYCLE_YEARLY
        ]);

        $amount = Subscription::calculatePrice($planType, $billingCycle);
        $startDate = $this->faker->dateTimeBetween('-6 months', 'now');
        $endDate = $this->calculateEndDate($startDate, $billingCycle);

        return [
            'user_id' => User::factory(),
            'plan_type' => $planType,
            'status' => $this->faker->randomElement([
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_CANCELLED,
                Subscription::STATUS_EXPIRED,
                Subscription::STATUS_PENDING
            ]),
            'amount' => $amount,
            'currency' => 'MXN',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'next_billing_date' => $endDate,
            'billing_cycle' => $billingCycle,
            'auto_renew' => $this->faker->boolean(80), // 80% de probabilidad de renovación automática
            'payment_method' => $this->faker->randomElement(['credit_card', 'debit_card', 'paypal', 'bank_transfer']),
            'payment_status' => $this->faker->randomElement([
                Subscription::PAYMENT_PENDING,
                Subscription::PAYMENT_COMPLETED,
                Subscription::PAYMENT_FAILED
            ]),
            'transaction_id' => 'txn_' . $this->faker->unique()->numerify('##########'),
            'notes' => $this->faker->optional()->sentence(),
            'features' => Subscription::getPlanConfig($planType)['features'],
        ];
    }

    /**
     * Estado para suscripción activa
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Subscription::STATUS_ACTIVE,
            'payment_status' => Subscription::PAYMENT_COMPLETED,
            'end_date' => now()->addMonth(),
        ]);
    }

    /**
     * Estado para suscripción expirada
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Subscription::STATUS_EXPIRED,
            'end_date' => now()->subDays($this->faker->numberBetween(1, 30)),
        ]);
    }

    /**
     * Estado para suscripción cancelada
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Subscription::STATUS_CANCELLED,
            'auto_renew' => false,
        ]);
    }

    /**
     * Estado para suscripción próxima a expirar
     */
    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Subscription::STATUS_ACTIVE,
            'payment_status' => Subscription::PAYMENT_COMPLETED,
            'end_date' => now()->addDays($this->faker->numberBetween(1, 7)),
        ]);
    }

    /**
     * Estado para plan básico
     */
    public function basic(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan_type' => Subscription::PLAN_BASIC,
            'features' => Subscription::getPlanConfig(Subscription::PLAN_BASIC)['features'],
        ]);
    }

    /**
     * Estado para plan premium
     */
    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan_type' => Subscription::PLAN_PREMIUM,
            'features' => Subscription::getPlanConfig(Subscription::PLAN_PREMIUM)['features'],
        ]);
    }

    /**
     * Estado para plan enterprise
     */
    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan_type' => Subscription::PLAN_ENTERPRISE,
            'features' => Subscription::getPlanConfig(Subscription::PLAN_ENTERPRISE)['features'],
        ]);
    }

    /**
     * Calcular fecha de fin según ciclo de facturación
     */
    private function calculateEndDate($startDate, $billingCycle): \Carbon\Carbon
    {
        $start = \Carbon\Carbon::parse($startDate);

        return match ($billingCycle) {
            Subscription::CYCLE_MONTHLY => $start->addMonth(),
            Subscription::CYCLE_QUARTERLY => $start->addMonths(3),
            Subscription::CYCLE_YEARLY => $start->addYear(),
            default => $start->addMonth(),
        };
    }
} 