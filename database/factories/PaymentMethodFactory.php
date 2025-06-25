<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = [
            PaymentMethod::TYPE_CARD,
            PaymentMethod::TYPE_BANK_ACCOUNT,
        ];

        $brands = [
            PaymentMethod::BRAND_VISA,
            PaymentMethod::BRAND_MASTERCARD,
            PaymentMethod::BRAND_AMEX,
            PaymentMethod::BRAND_DISCOVER,
        ];

        $type = $this->faker->randomElement($types);
        $brand = $type === PaymentMethod::TYPE_CARD ? $this->faker->randomElement($brands) : null;
        $last4 = $type === PaymentMethod::TYPE_CARD ? $this->faker->numerify('####') : null;

        return [
            'user_id' => User::factory(),
            'stripe_payment_method_id' => 'pm_' . $this->faker->regexify('[A-Za-z0-9]{24}'),
            'type' => $type,
            'last4' => $last4,
            'brand' => $brand,
            'is_default' => false,
            'metadata' => [
                'fingerprint' => $type === PaymentMethod::TYPE_CARD ? $this->faker->regexify('[A-Za-z0-9]{16}') : null,
                'country' => $type === PaymentMethod::TYPE_CARD ? $this->faker->countryCode() : null,
                'exp_month' => $type === PaymentMethod::TYPE_CARD ? $this->faker->numberBetween(1, 12) : null,
                'exp_year' => $type === PaymentMethod::TYPE_CARD ? $this->faker->numberBetween(date('Y'), date('Y') + 10) : null,
            ],
        ];
    }

    /**
     * Indicate that the payment method is a card.
     */
    public function card(): static
    {
        $brands = [
            PaymentMethod::BRAND_VISA,
            PaymentMethod::BRAND_MASTERCARD,
            PaymentMethod::BRAND_AMEX,
            PaymentMethod::BRAND_DISCOVER,
            PaymentMethod::BRAND_JCB,
            PaymentMethod::BRAND_DINERS_CLUB,
        ];

        return $this->state(fn (array $attributes) => [
            'type' => PaymentMethod::TYPE_CARD,
            'brand' => $this->faker->randomElement($brands),
            'last4' => $this->faker->numerify('####'),
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'fingerprint' => $this->faker->regexify('[A-Za-z0-9]{16}'),
                'country' => $this->faker->countryCode(),
                'exp_month' => $this->faker->numberBetween(1, 12),
                'exp_year' => $this->faker->numberBetween(date('Y'), date('Y') + 10),
            ]),
        ]);
    }

    /**
     * Indicate that the payment method is a bank account.
     */
    public function bankAccount(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => PaymentMethod::TYPE_BANK_ACCOUNT,
            'brand' => null,
            'last4' => $this->faker->numerify('####'),
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'fingerprint' => null,
                'country' => $this->faker->countryCode(),
                'exp_month' => null,
                'exp_year' => null,
            ]),
        ]);
    }

    /**
     * Indicate that the payment method is the default.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that the payment method is a Visa card.
     */
    public function visa(): static
    {
        return $this->card()->state(fn (array $attributes) => [
            'brand' => PaymentMethod::BRAND_VISA,
        ]);
    }

    /**
     * Indicate that the payment method is a Mastercard.
     */
    public function mastercard(): static
    {
        return $this->card()->state(fn (array $attributes) => [
            'brand' => PaymentMethod::BRAND_MASTERCARD,
        ]);
    }

    /**
     * Indicate that the payment method is an American Express card.
     */
    public function amex(): static
    {
        return $this->card()->state(fn (array $attributes) => [
            'brand' => PaymentMethod::BRAND_AMEX,
        ]);
    }
} 