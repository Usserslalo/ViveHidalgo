<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Evento extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'eventos';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'start_date',
        'end_date',
        'location',
        'latitude',
        'longitude',
        'price',
        'capacity',
        'current_attendees',
        'status',
        'is_featured',
        'main_image',
        'gallery',
        'contact_info',
        'organizer_name',
        'organizer_email',
        'organizer_phone',
        'website_url',
        'social_media',
        'tags',
        'user_id',
        'destino_id'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'price' => 'decimal:2',
        'capacity' => 'integer',
        'current_attendees' => 'integer',
        'is_featured' => 'boolean',
        'latitude' => 'decimal:8,6',
        'longitude' => 'decimal:9,6',
        'gallery' => 'array',
        'contact_info' => 'array',
        'social_media' => 'array',
        'tags' => 'array'
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * Boot function to set slug on creation
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($evento) {
            if (empty($evento->slug)) {
                $evento->slug = Str::slug($evento->name);
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Scope a query to only include published eventos.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope a query to only include featured eventos.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include upcoming eventos.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>=', now());
    }

    /**
     * Scope a query to only include ongoing eventos.
     */
    public function scopeOngoing($query)
    {
        return $query->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
    }

    /**
     * Get the user that owns the evento.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the destino associated with the evento.
     */
    public function destino(): BelongsTo
    {
        return $this->belongsTo(Destino::class);
    }

    /**
     * Get the categorias for the evento.
     */
    public function categorias(): BelongsToMany
    {
        return $this->belongsToMany(Categoria::class, 'evento_categoria');
    }

    /**
     * Get the caracteristicas for the evento.
     */
    public function caracteristicas(): BelongsToMany
    {
        return $this->belongsToMany(Caracteristica::class, 'evento_caracteristica');
    }

    /**
     * Get the tags for the evento.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'evento_tag');
    }

    /**
     * Check if evento is upcoming
     */
    public function isUpcoming(): bool
    {
        return $this->start_date->isFuture();
    }

    /**
     * Check if evento is ongoing
     */
    public function isOngoing(): bool
    {
        return $this->start_date->isPast() && $this->end_date->isFuture();
    }

    /**
     * Check if evento is past
     */
    public function isPast(): bool
    {
        return $this->end_date->isPast();
    }

    /**
     * Get available capacity
     */
    public function getAvailableCapacityAttribute(): int
    {
        return max(0, $this->capacity - $this->current_attendees);
    }

    /**
     * Get capacity percentage
     */
    public function getCapacityPercentageAttribute(): float
    {
        if ($this->capacity === 0) return 0;
        return round(($this->current_attendees / $this->capacity) * 100, 2);
    }

    /**
     * Get duration in days
     */
    public function getDurationDaysAttribute(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Get days until start
     */
    public function getDaysUntilStartAttribute(): int
    {
        return max(0, now()->diffInDays($this->start_date, false));
    }
} 