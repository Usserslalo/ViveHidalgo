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
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
        return $this->hasMany(Destino::class, 'provider_id');
    }

    /**
     * Relación con promociones del proveedor
     */
    public function promociones()
    {
        return $this->hasMany(Promocion::class, 'provider_id');
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
}
