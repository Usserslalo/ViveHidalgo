<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Destino;
use App\Models\Promocion;
use App\Models\Review;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $eventTypes = [
            AuditLog::EVENT_CREATED,
            AuditLog::EVENT_UPDATED,
            AuditLog::EVENT_DELETED,
            AuditLog::EVENT_LOGIN,
            AuditLog::EVENT_LOGOUT,
            AuditLog::EVENT_SUBSCRIPTION_CREATED,
            AuditLog::EVENT_SUBSCRIPTION_CANCELLED,
            AuditLog::EVENT_REVIEW_APPROVED,
            AuditLog::EVENT_REVIEW_REJECTED,
            AuditLog::EVENT_PROMOTION_EXPIRED,
        ];

        $auditableTypes = [
            User::class,
            Destino::class,
            Promocion::class,
            Review::class,
            Subscription::class,
        ];

        $eventType = $this->faker->randomElement($eventTypes);
        $auditableType = $this->faker->randomElement($auditableTypes);

        return [
            'user_id' => User::factory(),
            'event_type' => $eventType,
            'auditable_type' => $auditableType,
            'auditable_id' => $this->faker->numberBetween(1, 100),
            'old_values' => $this->generateOldValues($eventType, $auditableType),
            'new_values' => $this->generateNewValues($eventType, $auditableType),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'url' => $this->faker->url(),
            'method' => $this->faker->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
            'description' => $this->generateDescription($eventType, $auditableType),
            'metadata' => $this->generateMetadata($eventType),
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'updated_at' => function (array $attributes) {
                return $attributes['created_at'];
            },
        ];
    }

    /**
     * Estado para logs de creación
     */
    public function created(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => AuditLog::EVENT_CREATED,
            'old_values' => null,
            'new_values' => $this->generateNewValues(AuditLog::EVENT_CREATED, $attributes['auditable_type'] ?? User::class),
        ]);
    }

    /**
     * Estado para logs de actualización
     */
    public function updated(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => AuditLog::EVENT_UPDATED,
            'old_values' => $this->generateOldValues(AuditLog::EVENT_UPDATED, $attributes['auditable_type'] ?? User::class),
            'new_values' => $this->generateNewValues(AuditLog::EVENT_UPDATED, $attributes['auditable_type'] ?? User::class),
        ]);
    }

    /**
     * Estado para logs de eliminación
     */
    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => AuditLog::EVENT_DELETED,
            'old_values' => $this->generateOldValues(AuditLog::EVENT_DELETED, $attributes['auditable_type'] ?? User::class),
            'new_values' => null,
        ]);
    }

    /**
     * Estado para logs de login
     */
    public function login(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => AuditLog::EVENT_LOGIN,
            'auditable_type' => User::class,
            'old_values' => null,
            'new_values' => null,
            'description' => 'Usuario inició sesión',
        ]);
    }

    /**
     * Estado para logs de logout
     */
    public function logout(): static
    {
        return $this->state(fn (array $attributes) => [
            'event_type' => AuditLog::EVENT_LOGOUT,
            'auditable_type' => User::class,
            'old_values' => null,
            'new_values' => null,
            'description' => 'Usuario cerró sesión',
        ]);
    }

    /**
     * Generar valores antiguos según el tipo de evento y modelo
     */
    private function generateOldValues(string $eventType, string $auditableType): ?array
    {
        if ($eventType === AuditLog::EVENT_CREATED || $eventType === AuditLog::EVENT_LOGIN || $eventType === AuditLog::EVENT_LOGOUT) {
            return null;
        }

        return match ($auditableType) {
            User::class => [
                'name' => $this->faker->name(),
                'email' => $this->faker->email(),
                'is_active' => $this->faker->boolean(),
            ],
            Destino::class => [
                'name' => $this->faker->words(3, true),
                'description' => $this->faker->sentence(),
                'status' => 'draft',
            ],
            Promocion::class => [
                'title' => $this->faker->words(2, true),
                'discount_percentage' => $this->faker->numberBetween(5, 50),
                'is_active' => true,
            ],
            Review::class => [
                'rating' => $this->faker->numberBetween(1, 5),
                'comment' => $this->faker->sentence(),
                'is_approved' => false,
            ],
            Subscription::class => [
                'plan_type' => 'basic',
                'status' => 'active',
                'auto_renew' => true,
            ],
            default => null,
        };
    }

    /**
     * Generar valores nuevos según el tipo de evento y modelo
     */
    private function generateNewValues(string $eventType, string $auditableType): ?array
    {
        if ($eventType === AuditLog::EVENT_DELETED || $eventType === AuditLog::EVENT_LOGOUT) {
            return null;
        }

        return match ($auditableType) {
            User::class => [
                'name' => $this->faker->name(),
                'email' => $this->faker->email(),
                'is_active' => $this->faker->boolean(),
            ],
            Destino::class => [
                'name' => $this->faker->words(3, true),
                'description' => $this->faker->sentence(),
                'status' => 'published',
            ],
            Promocion::class => [
                'title' => $this->faker->words(2, true),
                'discount_percentage' => $this->faker->numberBetween(5, 50),
                'is_active' => $this->faker->boolean(),
            ],
            Review::class => [
                'rating' => $this->faker->numberBetween(1, 5),
                'comment' => $this->faker->sentence(),
                'is_approved' => $this->faker->boolean(),
            ],
            Subscription::class => [
                'plan_type' => $this->faker->randomElement(['basic', 'premium', 'enterprise']),
                'status' => $this->faker->randomElement(['active', 'cancelled', 'expired']),
                'auto_renew' => $this->faker->boolean(),
            ],
            default => null,
        };
    }

    /**
     * Generar descripción según el tipo de evento
     */
    private function generateDescription(string $eventType, string $auditableType): string
    {
        $modelName = class_basename($auditableType);
        
        return match ($eventType) {
            AuditLog::EVENT_CREATED => "Creó {$modelName}",
            AuditLog::EVENT_UPDATED => "Actualizó {$modelName}",
            AuditLog::EVENT_DELETED => "Eliminó {$modelName}",
            AuditLog::EVENT_LOGIN => "Usuario inició sesión",
            AuditLog::EVENT_LOGOUT => "Usuario cerró sesión",
            AuditLog::EVENT_SUBSCRIPTION_CREATED => "Creó suscripción",
            AuditLog::EVENT_SUBSCRIPTION_CANCELLED => "Canceló suscripción",
            AuditLog::EVENT_REVIEW_APPROVED => "Aprobó reseña",
            AuditLog::EVENT_REVIEW_REJECTED => "Rechazó reseña",
            AuditLog::EVENT_PROMOTION_EXPIRED => "Promoción expirada",
            default => "Acción realizada en {$modelName}",
        };
    }

    /**
     * Generar metadata según el tipo de evento
     */
    private function generateMetadata(string $eventType): array
    {
        return match ($eventType) {
            AuditLog::EVENT_LOGIN, AuditLog::EVENT_LOGOUT => [
                'session_id' => $this->faker->uuid(),
                'device_type' => $this->faker->randomElement(['desktop', 'mobile', 'tablet']),
            ],
            AuditLog::EVENT_SUBSCRIPTION_CREATED, AuditLog::EVENT_SUBSCRIPTION_CANCELLED => [
                'plan_type' => $this->faker->randomElement(['basic', 'premium', 'enterprise']),
                'amount' => $this->faker->randomFloat(2, 99.99, 2999.99),
                'payment_method' => $this->faker->randomElement(['credit_card', 'paypal']),
            ],
            default => [
                'source' => 'api',
                'version' => '1.0',
            ],
        };
    }
} 