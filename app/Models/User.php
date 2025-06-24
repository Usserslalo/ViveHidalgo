<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'profile_photo',
        'is_active',
        'email_verified_at',
        // Campos de proveedor
        'company_name',
        'company_description',
        'website',
        'logo_path',
        'business_license_path',
        'tax_id',
        'contact_person',
        'contact_phone',
        'contact_email',
        'business_type',
        'business_hours',
        'is_verified_provider',
        'verified_at',
        'verification_notes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'verification_notes', // Ocultar notas de verificación
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'business_hours' => 'array',
            'is_verified_provider' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * Relación con destinos favoritos
     */
    public function favoritos()
    {
        return $this->belongsToMany(Destino::class, 'favoritos', 'user_id', 'destino_id')
                    ->withTimestamps();
    }

    /**
     * Relación con destinos del proveedor
     */
    public function destinos()
    {
        return $this->hasMany(Destino::class, 'user_id');
    }

    /**
     * Relación con promociones del proveedor (a través de destinos)
     */
    public function promociones()
    {
        return $this->hasManyThrough(Promocion::class, Destino::class, 'user_id', 'destino_id');
    }

    /**
     * Relación con suscripción del proveedor
     */
    public function subscription()
    {
        return $this->hasOne(Subscription::class, 'user_id');
    }

    /**
     * Verificar si el usuario es administrador
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Verificar si el usuario es proveedor
     */
    public function isProvider(): bool
    {
        return $this->hasRole('provider');
    }

    /**
     * Verificar si el usuario es turista
     */
    public function isTourist(): bool
    {
        return $this->hasRole('tourist');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole(['admin', 'provider']);
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return null;
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Verificar si el usuario es un proveedor verificado
     */
    public function isVerifiedProvider(): bool
    {
        return $this->isProvider() && $this->is_verified_provider;
    }

    /**
     * Obtener la URL del logo del proveedor
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        return asset('storage/' . $this->logo_path);
    }

    /**
     * Obtener la URL de la licencia de negocio
     */
    public function getBusinessLicenseUrlAttribute(): ?string
    {
        if (!$this->business_license_path) {
            return null;
        }

        return asset('storage/' . $this->business_license_path);
    }

    /**
     * Obtener estadísticas del proveedor
     */
    public function getProviderStatsAttribute(): array
    {
        if (!$this->isProvider()) {
            return [];
        }

        return [
            'destinos_count' => $this->destinos()->count(),
            'promociones_count' => $this->promociones()->count(),
            'total_reviews' => $this->destinos()->withCount('reviews')->get()->sum('reviews_count'),
            'average_rating' => $this->destinos()->withAvg('reviews', 'rating')->get()->avg('reviews_avg_rating') ?? 0,
            'member_since' => $this->created_at->format('Y-m-d'),
            'verified_since' => $this->verified_at?->format('Y-m-d'),
        ];
    }

    /**
     * Obtener horarios de negocio formateados
     */
    public function getFormattedBusinessHoursAttribute(): array
    {
        if (!$this->business_hours) {
            return [];
        }

        $days = [
            'monday' => 'Lunes',
            'tuesday' => 'Martes',
            'wednesday' => 'Miércoles',
            'thursday' => 'Jueves',
            'friday' => 'Viernes',
            'saturday' => 'Sábado',
            'sunday' => 'Domingo',
        ];

        $formatted = [];
        foreach ($this->business_hours as $day => $hours) {
            if (isset($days[$day])) {
                $formatted[$days[$day]] = $hours;
            }
        }

        return $formatted;
    }

    /**
     * Verificar si el proveedor está abierto en un momento específico
     */
    public function isOpenAt(string $dayOfWeek = null, string $time = null): bool
    {
        if (!$this->business_hours || !$this->isProvider()) {
            return false;
        }

        $dayOfWeek = $dayOfWeek ?? strtolower(date('l'));
        $time = $time ?? date('H:i');

        if (!isset($this->business_hours[$dayOfWeek])) {
            return false;
        }

        $hours = $this->business_hours[$dayOfWeek];
        
        if ($hours['closed'] ?? false) {
            return false;
        }

        $currentTime = strtotime($time);
        $openTime = strtotime($hours['open'] ?? '00:00');
        $closeTime = strtotime($hours['close'] ?? '23:59');

        return $currentTime >= $openTime && $currentTime <= $closeTime;
    }

    /**
     * Verificar si el usuario tiene una suscripción activa
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscription()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('end_date', '>', now())
            ->exists();
    }

    /**
     * Obtener la suscripción activa
     */
    public function getActiveSubscription()
    {
        return $this->subscription()
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('end_date', '>', now())
            ->first();
    }

    /**
     * Verificar si el usuario puede crear más destinos según su plan
     */
    public function canCreateDestino(): bool
    {
        if (!$this->isProvider()) {
            return false;
        }

        if (!$this->hasActiveSubscription()) {
            return false;
        }

        $subscription = $this->getActiveSubscription();
        $planConfig = $subscription->plan_config;
        $destinosLimit = $planConfig['features']['destinos_limit'] ?? 0;

        // Si el límite es -1, es ilimitado
        if ($destinosLimit === -1) {
            return true;
        }

        return $this->destinos()->count() < $destinosLimit;
    }

    /**
     * Verificar si el usuario puede crear más promociones según su plan
     */
    public function canCreatePromocion(): bool
    {
        if (!$this->isProvider()) {
            return false;
        }

        if (!$this->hasActiveSubscription()) {
            return false;
        }

        $subscription = $this->getActiveSubscription();
        $planConfig = $subscription->plan_config;
        $promocionesLimit = $planConfig['features']['promociones_limit'] ?? 0;

        // Si el límite es -1, es ilimitado
        if ($promocionesLimit === -1) {
            return true;
        }

        return $this->promociones()->count() < $promocionesLimit;
    }

    /**
     * Obtener estadísticas de suscripción
     */
    public function getSubscriptionStatsAttribute(): array
    {
        if (!$this->subscription) {
            return [
                'has_subscription' => false,
                'status' => 'no_subscription',
                'plan_name' => null,
                'days_remaining' => 0,
                'is_expiring_soon' => false,
            ];
        }

        return [
            'has_subscription' => true,
            'status' => $this->subscription->status,
            'plan_name' => $this->subscription->plan_config['name'] ?? null,
            'plan_type' => $this->subscription->plan_type,
            'days_remaining' => $this->subscription->days_remaining,
            'is_expiring_soon' => $this->subscription->isExpiringSoon(),
            'is_active' => $this->subscription->isActive(),
            'auto_renew' => $this->subscription->auto_renew,
            'next_billing_date' => $this->subscription->next_billing_date?->format('Y-m-d'),
        ];
    }

    /**
     * Obtener límites del plan actual
     */
    public function getPlanLimitsAttribute(): array
    {
        if (!$this->hasActiveSubscription()) {
            return [
                'destinos_limit' => 0,
                'promociones_limit' => 0,
                'destinos_used' => 0,
                'promociones_used' => 0,
                'can_create_destino' => false,
                'can_create_promocion' => false,
            ];
        }

        $subscription = $this->getActiveSubscription();
        $planConfig = $subscription->plan_config;
        $destinosLimit = $planConfig['features']['destinos_limit'] ?? 0;
        $promocionesLimit = $planConfig['features']['promociones_limit'] ?? 0;

        return [
            'destinos_limit' => $destinosLimit,
            'promociones_limit' => $promocionesLimit,
            'destinos_used' => $this->destinos()->count(),
            'promociones_used' => $this->promociones()->count(),
            'can_create_destino' => $this->canCreateDestino(),
            'can_create_promocion' => $this->canCreatePromocion(),
        ];
    }
}
