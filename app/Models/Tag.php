<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * @OA\Schema(
 *     schema="Tag",
 *     type="object",
 *     title="Tag",
 *     required={"id", "name", "slug"},
 *     @OA\Property(property="id", type="integer", format="int64", description="ID único del tag"),
 *     @OA\Property(property="name", type="string", description="Nombre del tag"),
 *     @OA\Property(property="slug", type="string", description="Slug único del tag"),
 *     @OA\Property(property="color", type="string", nullable=true, description="Color del tag para UI"),
 *     @OA\Property(property="description", type="string", nullable=true, description="Descripción del tag"),
 *     @OA\Property(property="is_active", type="boolean", description="Indica si el tag está activo"),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly=true)
 * )
 */
class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'color',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Los destinos que tienen este tag
     */
    public function destinos(): BelongsToMany
    {
        return $this->belongsToMany(Destino::class, 'destino_tag')
                    ->withTimestamps();
    }

    /**
     * Scope para tags activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para ordenar por nombre
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('name', 'asc');
    }

    /**
     * Boot method para generar slug automáticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });

        static::updating(function ($tag) {
            if ($tag->isDirty('name') && empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    /**
     * Obtener el número de destinos que usan este tag
     */
    public function getDestinosCountAttribute()
    {
        return $this->destinos()->count();
    }
} 