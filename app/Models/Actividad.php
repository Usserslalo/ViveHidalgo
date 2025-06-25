<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Actividad extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'actividades';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'duration_minutes',
        'price',
        'currency',
        'max_participants',
        'min_participants',
        'difficulty_level',
        'age_min',
        'age_max',
        'is_available',
        'is_featured',
        'main_image',
        'gallery',
        'included_items',
        'excluded_items',
        'what_to_bring',
        'safety_notes',
        'cancellation_policy',
        'meeting_point',
        'meeting_time',
        'seasonal_availability',
        'weather_dependent',
        'user_id',
        'destino_id'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_minutes' => 'integer',
        'max_participants' => 'integer',
        'min_participants' => 'integer',
        'age_min' => 'integer',
        'age_max' => 'integer',
        'is_available' => 'boolean',
        'is_featured' => 'boolean',
        'weather_dependent' => 'boolean',
        'gallery' => 'array',
        'included_items' => 'array',
        'excluded_items' => 'array',
        'what_to_bring' => 'array',
        'safety_notes' => 'array',
        'seasonal_availability' => 'array'
    ];

    protected $dates = [
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

        static::creating(function ($actividad) {
            if (empty($actividad->slug)) {
                $actividad->slug = Str::slug($actividad->name);
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
     * Scope a query to only include available actividades.
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope a query to only include featured actividades.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to filter by difficulty level.
     */
    public function scopeByDifficulty($query, $level)
    {
        return $query->where('difficulty_level', $level);
    }

    /**
     * Scope a query to filter by price range.
     */
    public function scopeByPriceRange($query, $min, $max)
    {
        return $query->whereBetween('price', [$min, $max]);
    }

    /**
     * Scope a query to filter by duration range.
     */
    public function scopeByDurationRange($query, $min, $max)
    {
        return $query->whereBetween('duration_minutes', [$min, $max]);
    }

    /**
     * Get the user that owns the actividad.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the destino associated with the actividad.
     */
    public function destino(): BelongsTo
    {
        return $this->belongsTo(Destino::class);
    }

    /**
     * Get the categorias for the actividad.
     */
    public function categorias(): BelongsToMany
    {
        return $this->belongsToMany(Categoria::class, 'actividad_categoria');
    }

    /**
     * Get the caracteristicas for the actividad.
     */
    public function caracteristicas(): BelongsToMany
    {
        return $this->belongsToMany(Caracteristica::class, 'actividad_caracteristica');
    }

    /**
     * Get the tags for the actividad.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'actividad_tag');
    }

    /**
     * Get duration in hours
     */
    public function getDurationHoursAttribute(): float
    {
        return round($this->duration_minutes / 60, 1);
    }

    /**
     * Get duration formatted
     */
    public function getDurationFormattedAttribute(): string
    {
        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$minutes}m";
        }
    }

    /**
     * Get price formatted
     */
    public function getPriceFormattedAttribute(): string
    {
        $currency = $this->currency ?? 'MXN';
        return $currency . ' ' . number_format($this->price, 2);
    }

    /**
     * Get age range formatted
     */
    public function getAgeRangeFormattedAttribute(): string
    {
        if ($this->age_min && $this->age_max) {
            return "{$this->age_min}-{$this->age_max} años";
        } elseif ($this->age_min) {
            return "{$this->age_min}+ años";
        } elseif ($this->age_max) {
            return "Hasta {$this->age_max} años";
        }
        return "Todas las edades";
    }

    /**
     * Get participant range formatted
     */
    public function getParticipantRangeFormattedAttribute(): string
    {
        if ($this->min_participants && $this->max_participants) {
            return "{$this->min_participants}-{$this->max_participants} personas";
        } elseif ($this->max_participants) {
            return "Hasta {$this->max_participants} personas";
        } elseif ($this->min_participants) {
            return "Mínimo {$this->min_participants} personas";
        }
        return "Sin límite";
    }

    /**
     * Check if actividad is currently available
     */
    public function isCurrentlyAvailable(): bool
    {
        if (!$this->is_available) {
            return false;
        }

        // Verificar disponibilidad estacional si está configurada
        if (!empty($this->seasonal_availability)) {
            $currentMonth = now()->month;
            return in_array($currentMonth, $this->seasonal_availability);
        }

        return true;
    }
} 