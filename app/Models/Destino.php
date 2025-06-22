<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Destino extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'region_id',
        'name',
        'slug',
        'short_description',
        'description',
        'address',
        'ubicacion_referencia',
        'latitude',
        'longitude',
        'location',
        'phone',
        'whatsapp',
        'email',
        'website',
        'status',
        'is_featured',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'location' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function categorias(): BelongsToMany
    {
        return $this->belongsToMany(Categoria::class, 'categoria_destino')
                    ->withTimestamps();
    }

    public function promociones(): HasMany
    {
        return $this->hasMany(Promocion::class);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favoritos');
    }

    /**
     * Relación muchos a muchos con características
     */
    public function caracteristicas()
    {
        return $this->belongsToMany(Caracteristica::class, 'caracteristica_destino')
                    ->withTimestamps();
    }

    /**
     * Scope para destinos publicados
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope para destinos por región
     */
    public function scopeByRegion($query, $regionId)
    {
        return $query->where('region_id', $regionId);
    }

    /**
     * Scope para destinos por categoría
     */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->whereHas('categorias', function ($q) use ($categoryId) {
            $q->where('categorias.id', $categoryId);
        });
    }

    /**
     * Scope para destinos por características
     */
    public function scopeByCharacteristics($query, $characteristicIds)
    {
        return $query->whereHas('caracteristicas', function ($q) use ($characteristicIds) {
            $q->whereIn('caracteristicas.id', (array) $characteristicIds);
        });
    }

    /**
     * Scope para calcular la distancia a un punto.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $latitude
     * @param float $longitude
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithDistance($query, $latitude, $longitude)
    {
        $haversine = "(6371 * acos(cos(radians(?))
                        * cos(radians(latitude))
                        * cos(radians(longitude) - radians(?))
                        + sin(radians(?))
                        * sin(radians(latitude))))";

        return $query
            ->select('*') // Selecciona todas las columnas existentes
            ->selectRaw("{$haversine} AS distancia_km", [$latitude, $longitude, $latitude]);
    }

    /**
     * Scope para filtrar destinos dentro de un radio.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $latitude
     * @param float $longitude
     * @param int $radiusInKm
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithinRadius($query, $latitude, $longitude, $radiusInKm)
    {
        return $query->having('distancia_km', '<=', $radiusInKm)
                     ->orderBy('distancia_km', 'asc');
    }

    /**
     * Boot method para generar slug automáticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($destino) {
            if (empty($destino->slug)) {
                $destino->slug = Str::slug($destino->name);
            }
        });

        static::updating(function ($destino) {
            if ($destino->isDirty('name') && empty($destino->slug)) {
                $destino->slug = Str::slug($destino->name);
            }
        });
    }

    /**
     * Obtener características activas
     */
    public function getCaracteristicasActivasAttribute()
    {
        return $this->caracteristicas()->activas()->get();
    }

    /**
     * Verificar si tiene una característica específica
     */
    public function tieneCaracteristica($caracteristicaId)
    {
        return $this->caracteristicas()->where('caracteristicas.id', $caracteristicaId)->exists();
    }

    /**
     * Obtener características por tipo
     */
    public function caracteristicasPorTipo($tipo)
    {
        return $this->caracteristicas()->porTipo($tipo)->get();
    }

    /**
     * Accessor para el campo location
     */
    public function getLocationAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }
        return $value;
    }

    /**
     * Mutator para el campo location
     */
    public function setLocationAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['location'] = json_encode($value);
        } else {
            $this->attributes['location'] = $value;
        }
    }
}
