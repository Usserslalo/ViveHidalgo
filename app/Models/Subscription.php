<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * @OA\Schema(
 *     schema="Subscription",
 *     type="object",
 *     title="Subscription",
 *     properties={
 *         @OA\Property(property="id", type="integer", readOnly=true, example=1),
 *         @OA\Property(property="user_id", type="integer", example=1),
 *         @OA\Property(property="plan_type", type="string", example="premium", description="Tipo de plan: basic, premium, enterprise"),
 *         @OA\Property(property="status", type="string", example="active", description="Estado: active, cancelled, expired, pending"),
 *         @OA\Property(property="amount", type="number", format="float", example=299.99),
 *         @OA\Property(property="currency", type="string", example="MXN"),
 *         @OA\Property(property="start_date", type="string", format="date", example="2025-01-01"),
 *         @OA\Property(property="end_date", type="string", format="date", example="2025-02-01"),
 *         @OA\Property(property="next_billing_date", type="string", format="date", nullable=true, example="2025-02-01"),
 *         @OA\Property(property="billing_cycle", type="string", example="monthly", description="Ciclo de facturación: monthly, quarterly, yearly"),
 *         @OA\Property(property="auto_renew", type="boolean", example=true),
 *         @OA\Property(property="payment_method", type="string", nullable=true, example="credit_card"),
 *         @OA\Property(property="payment_status", type="string", example="completed", description="Estado del pago: pending, completed, failed"),
 *         @OA\Property(property="transaction_id", type="string", nullable=true, example="txn_123456789"),
 *         @OA\Property(property="features", type="object", nullable=true, description="Características incluidas en el plan"),
 *         @OA\Property(property="created_at", type="string", format="date-time", readOnly=true),
 *         @OA\Property(property="updated_at", type="string", format="date-time", readOnly=true),
 *         @OA\Property(property="user", type="object", ref="#/components/schemas/User"),
 *     }
 * )
 */
class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_type',
        'status',
        'amount',
        'currency',
        'start_date',
        'end_date',
        'next_billing_date',
        'billing_cycle',
        'auto_renew',
        'payment_method',
        'payment_status',
        'transaction_id',
        'notes',
        'features',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_billing_date' => 'date',
        'auto_renew' => 'boolean',
        'features' => 'array',
    ];

    // Constantes para tipos de plan
    const PLAN_BASIC = 'basic';
    const PLAN_PREMIUM = 'premium';
    const PLAN_ENTERPRISE = 'enterprise';

    // Constantes para estados
    const STATUS_ACTIVE = 'active';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXPIRED = 'expired';
    const STATUS_PENDING = 'pending';

    // Constantes para ciclos de facturación
    const CYCLE_MONTHLY = 'monthly';
    const CYCLE_QUARTERLY = 'quarterly';
    const CYCLE_YEARLY = 'yearly';

    // Constantes para estados de pago
    const PAYMENT_PENDING = 'pending';
    const PAYMENT_COMPLETED = 'completed';
    const PAYMENT_FAILED = 'failed';

    /**
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope para suscripciones activas
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope para suscripciones expiradas
     */
    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED)
                    ->orWhere('end_date', '<', now());
    }

    /**
     * Scope para suscripciones próximas a expirar (7 días)
     */
    public function scopeExpiringSoon($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->where('end_date', '<=', now()->addDays(7))
                    ->where('end_date', '>', now());
    }

    /**
     * Verificar si la suscripción está activa
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && 
               $this->end_date >= now();
    }

    /**
     * Verificar si la suscripción está expirada
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED || 
               $this->end_date < now();
    }

    /**
     * Verificar si la suscripción está próxima a expirar
     */
    public function isExpiringSoon(): bool
    {
        return $this->isActive() && 
               $this->end_date <= now()->addDays(7);
    }

    /**
     * Obtener días restantes de la suscripción
     */
    public function getDaysRemainingAttribute(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return now()->diffInDays($this->end_date, false);
    }

    /**
     * Obtener el plan configurado
     */
    public function getPlanConfigAttribute(): array
    {
        return self::getPlanConfig($this->plan_type);
    }

    /**
     * Obtener configuración de un plan específico
     */
    public static function getPlanConfig(string $planType): array
    {
        $plans = [
            self::PLAN_BASIC => [
                'name' => 'Plan Básico',
                'price' => 99.99,
                'currency' => 'MXN',
                'billing_cycles' => [
                    self::CYCLE_MONTHLY => 99.99,
                    self::CYCLE_QUARTERLY => 269.99,
                    self::CYCLE_YEARLY => 999.99,
                ],
                'features' => [
                    'destinos_limit' => 5,
                    'promociones_limit' => 2,
                    'analytics_basic' => true,
                    'support_email' => true,
                ],
                'description' => 'Ideal para pequeños negocios turísticos',
            ],
            self::PLAN_PREMIUM => [
                'name' => 'Plan Premium',
                'price' => 299.99,
                'currency' => 'MXN',
                'billing_cycles' => [
                    self::CYCLE_MONTHLY => 299.99,
                    self::CYCLE_QUARTERLY => 809.99,
                    self::CYCLE_YEARLY => 2999.99,
                ],
                'features' => [
                    'destinos_limit' => 20,
                    'promociones_limit' => 10,
                    'analytics_advanced' => true,
                    'support_priority' => true,
                    'featured_listing' => true,
                    'custom_branding' => true,
                ],
                'description' => 'Perfecto para negocios turísticos en crecimiento',
            ],
            self::PLAN_ENTERPRISE => [
                'name' => 'Plan Enterprise',
                'price' => 599.99,
                'currency' => 'MXN',
                'billing_cycles' => [
                    self::CYCLE_MONTHLY => 599.99,
                    self::CYCLE_QUARTERLY => 1619.99,
                    self::CYCLE_YEARLY => 5999.99,
                ],
                'features' => [
                    'destinos_limit' => -1, // Ilimitado
                    'promociones_limit' => -1, // Ilimitado
                    'analytics_enterprise' => true,
                    'support_dedicated' => true,
                    'featured_listing' => true,
                    'custom_branding' => true,
                    'api_access' => true,
                    'white_label' => true,
                ],
                'description' => 'Solución completa para grandes empresas turísticas',
            ],
        ];

        return $plans[$planType] ?? $plans[self::PLAN_BASIC];
    }

    /**
     * Obtener todos los planes disponibles
     */
    public static function getAvailablePlans(): array
    {
        return [
            self::PLAN_BASIC => self::getPlanConfig(self::PLAN_BASIC),
            self::PLAN_PREMIUM => self::getPlanConfig(self::PLAN_PREMIUM),
            self::PLAN_ENTERPRISE => self::getPlanConfig(self::PLAN_ENTERPRISE),
        ];
    }

    /**
     * Calcular precio según ciclo de facturación
     */
    public static function calculatePrice(string $planType, string $billingCycle): float
    {
        $config = self::getPlanConfig($planType);
        return $config['billing_cycles'][$billingCycle] ?? $config['price'];
    }

    /**
     * Cancelar suscripción
     */
    public function cancel(): bool
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'auto_renew' => false,
        ]);

        return true;
    }

    /**
     * Renovar suscripción
     */
    public function renew(): bool
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'auto_renew' => true,
        ]);

        return true;
    }

    /**
     * Marcar como expirada
     */
    public function markAsExpired(): bool
    {
        $this->update(['status' => self::STATUS_EXPIRED]);
        return true;
    }
} 