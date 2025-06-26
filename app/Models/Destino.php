<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

/**
 * @OA\Schema(
 *     schema="Destino",
 *     type="object",
 *     title="Destino Turístico",
 *     description="Representa un destino turístico en la plataforma.",
 *     required={"id", "name", "slug", "status"},
 *     @OA\Property(property="id", type="integer", format="int64", description="ID único del destino."),
 *     @OA\Property(property="name", type="string", description="Nombre del destino."),
 *     @OA\Property(property="slug", type="string", description="Slug único para la URL."),
 *     @OA\Property(property="short_description", type="string", nullable=true, description="Descripción corta."),
 *     @OA\Property(property="description", type="string", nullable=true, description="Descripción completa y detallada."),
 *     @OA\Property(property="descripcion_corta", type="string", nullable=true, description="Descripción corta para listados."),
 *     @OA\Property(property="descripcion_larga", type="string", nullable=true, description="Descripción larga con HTML."),
 *     @OA\Property(property="address", type="string", nullable=true, description="Dirección física del lugar."),
 *     @OA\Property(property="latitude", type="number", format="float", nullable=true, description="Latitud para el mapa."),
 *     @OA\Property(property="longitude", type="number", format="float", nullable=true, description="Longitud para el mapa."),
 *     @OA\Property(property="phone", type="string", nullable=true, description="Teléfono de contacto."),
 *     @OA\Property(property="website", type="string", nullable=true, description="Sitio web oficial."),
 *     @OA\Property(property="status", type="string", enum={"published", "draft", "pending"}, description="Estado de publicación."),
 *     @OA\Property(property="average_rating", type="number", format="float", readOnly=true, description="Calificación promedio de reseñas (0-5)."),
 *     @OA\Property(property="reviews_count", type="integer", readOnly=true, description="Número total de reseñas aprobadas."),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly=true)
 * )
 */
class Destino extends Model
{
    use HasFactory, Searchable;

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'descripcion_corta' => $this->descripcion_corta,
            'descripcion_larga' => $this->descripcion_larga,
        ];
    }

    protected $fillable = [
        'user_id',
        'region_id',
        'name',
        'slug',
        'short_description',
        'description',
        'descripcion_corta',
        'descripcion_larga',
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
        'price_range',
        'visit_count',
        'favorite_count',
        'is_featured',
        'is_top',
        'titulo_seo',
        'descripcion_meta',
        'keywords',
        'open_graph_image',
        'indexar_seo',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_top' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'location' => 'array',
        'indexar_seo' => 'boolean',
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
     * Relación muchos a muchos con tags
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'destino_tag')
                    ->withTimestamps();
    }

    /**
     * Relación polimórfica con imágenes
     */
    public function imagenes(): MorphMany
    {
        return $this->morphMany(Imagen::class, 'imageable')->ordered();
    }

    /**
     * Obtener la imagen principal
     */
    public function imagenPrincipal()
    {
        return $this->morphOne(Imagen::class, 'imageable')->where('is_main', true);
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
     * Scope para destinos por tags
     */
    public function scopeByTags($query, $tagIds)
    {
        return $query->whereHas('tags', function ($q) use ($tagIds) {
            $q->whereIn('tags.id', (array) $tagIds);
        });
    }

    /**
     * Scope para destinos destacados (TOP)
     */
    public function scopeTop($query)
    {
        return $query->where('is_top', true);
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

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function updateReviewStats()
    {
        $this->average_rating = (float) $this->reviews()->where('is_approved', true)->avg('rating') ?: 0;
        $this->reviews_count = $this->reviews()->where('is_approved', true)->count();
        $this->save();
    }

    /**
     * Incrementar el contador de visitas
     */
    public function incrementVisitCount(): bool
    {
        return $this->increment('visit_count');
    }

    /**
     * Decrementar el contador de visitas
     */
    public function decrementVisitCount(): bool
    {
        return $this->decrement('visit_count');
    }

    /**
     * Incrementar el contador de favoritos
     */
    public function incrementFavoriteCount(): bool
    {
        return $this->increment('favorite_count');
    }

    /**
     * Decrementar el contador de favoritos
     */
    public function decrementFavoriteCount(): bool
    {
        return $this->decrement('favorite_count');
    }

    /**
     * Actualizar el contador de favoritos basado en la tabla pivot
     */
    public function updateFavoriteCount(): void
    {
        $this->favorite_count = $this->favoritedBy()->count();
        $this->save();
    }

    /**
     * Scope para filtrar por rango de precios
     */
    public function scopeByPriceRange($query, $priceRange)
    {
        return $query->where('price_range', $priceRange);
    }

    /**
     * Scope para destinos más visitados
     */
    public function scopeMostVisited($query, $limit = 10)
    {
        return $query->orderBy('visit_count', 'desc')->limit($limit);
    }

    /**
     * Scope para destinos más favoritos
     */
    public function scopeMostFavorited($query, $limit = 10)
    {
        return $query->orderBy('favorite_count', 'desc')->limit($limit);
    }

    /**
     * Obtener el texto del rango de precios
     */
    public function getPriceRangeTextAttribute(): string
    {
        return match($this->price_range) {
            'gratis' => 'Gratis',
            'economico' => 'Económico',
            'moderado' => 'Moderado',
            'premium' => 'Premium',
            default => 'No especificado'
        };
    }

    /**
     * Obtener el rango de precios en formato para filtros
     */
    public function getPriceRangeValueAttribute(): array
    {
        return match($this->price_range) {
            'gratis' => ['min' => 0, 'max' => 0],
            'economico' => ['min' => 1, 'max' => 500],
            'moderado' => ['min' => 501, 'max' => 2000],
            'premium' => ['min' => 2001, 'max' => null],
            default => ['min' => null, 'max' => null]
        };
    }

    /**
     * Obtener la imagen principal optimizada
     */
    public function getImagenPrincipalOptimizadaAttribute()
    {
        $imagen = $this->imagenPrincipal;
        if (!$imagen) {
            return [
                'original' => 'https://via.placeholder.com/400x300/6B7280/FFFFFF?text=' . urlencode($this->name),
                'large' => 'https://via.placeholder.com/800x600/6B7280/FFFFFF?text=' . urlencode($this->name),
                'medium' => 'https://via.placeholder.com/400x300/6B7280/FFFFFF?text=' . urlencode($this->name),
                'thumbnail' => 'https://via.placeholder.com/150x150/6B7280/FFFFFF?text=' . urlencode($this->name),
                'alt' => 'Imagen de ' . $this->name
            ];
        }

        return [
            'original' => $imagen->url,
            'large' => $this->generateImageSize($imagen->url, 'large'),
            'medium' => $this->generateImageSize($imagen->url, 'medium'),
            'thumbnail' => $this->generateImageSize($imagen->url, 'thumbnail'),
            'alt' => $imagen->alt ?? 'Imagen de ' . $this->name
        ];
    }

    /**
     * Obtener galería optimizada
     */
    public function getGaleriaOptimizadaAttribute()
    {
        return $this->imagenes->map(function ($imagen) {
            return [
                'id' => $imagen->id,
                'url' => $imagen->url,
                'thumbnail' => $this->generateImageSize($imagen->url, 'thumbnail'),
                'alt' => $imagen->alt ?? 'Imagen de ' . $this->name,
                'is_main' => $imagen->is_main,
                'order' => $imagen->orden,
                'sizes' => [
                    'original' => $imagen->url,
                    'large' => $this->generateImageSize($imagen->url, 'large'),
                    'medium' => $this->generateImageSize($imagen->url, 'medium'),
                    'thumbnail' => $this->generateImageSize($imagen->url, 'thumbnail')
                ]
            ];
        });
    }

    /**
     * Generar URL de imagen con tamaño específico
     */
    private function generateImageSize(string $originalUrl, string $size): string
    {
        // Para pruebas, usar placeholder. En producción, generar tamaños reales
        if (str_contains($originalUrl, 'placeholder.com')) {
            $sizes = [
                'large' => '800x600',
                'medium' => '400x300',
                'thumbnail' => '150x150'
            ];
            return str_replace('400x300', $sizes[$size] ?? '400x300', $originalUrl);
        }
        
        // Si es una URL real, generar tamaño
        $pathInfo = pathinfo($originalUrl);
        return $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_' . $size . '.' . $pathInfo['extension'];
    }

    /**
     * Obtener características formateadas para frontend
     */
    public function getCaracteristicasFormateadasAttribute()
    {
        return $this->caracteristicas->take(3)->map(function ($caracteristica) {
            return [
                'id' => $caracteristica->id,
                'name' => $caracteristica->nombre,
                'icon' => $caracteristica->icono ?? '📍'
            ];
        });
    }

    /**
     * Obtener datos visuales optimizados para listados
     */
    public function getDatosVisualesAttribute()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'imagen_principal' => $this->imagen_principal_optimizada['medium'],
            'rating' => $this->average_rating ?? 4.5,
            'reviews_count' => $this->reviews_count ?? 0,
            'favorite_count' => $this->favorite_count ?? 0,
            'price_range' => $this->price_range ?? 'moderado',
            'caracteristicas' => $this->caracteristicas_formateadas->pluck('name')->toArray(),
            'region' => $this->region?->name ?? 'Hidalgo',
            'distance_km' => $this->distance_km ?? 15.2
        ];
    }

    /**
     * Obtener destinos TOP rotados para mostrar en portada
     * Si hay más de $maxDestinos TOP, selecciona aleatoriamente $maxDestinos
     */
    public static function getTopDestinosRotados(int $maxDestinos = 8): \Illuminate\Database\Eloquent\Collection
    {
        $topDestinos = static::where('status', 'published')
            ->where('is_top', true)
            ->with([
                'region:id,name',
                'imagenes' => function ($q) { $q->main(); },
                'caracteristicas' => function ($q) { $q->activas(); }
            ])
            ->orderByDesc('average_rating')
            ->get();

        // Si hay más destinos TOP que el máximo permitido, seleccionar aleatoriamente
        if ($topDestinos->count() > $maxDestinos) {
            return $topDestinos->shuffle()->take($maxDestinos);
        }

        return $topDestinos;
    }

    /**
     * Verificar si el destino cumple criterios automáticos para ser TOP
     */
    public function cumpleCriteriosTop(): bool
    {
        return $this->average_rating >= 4.5 
            && $this->favorite_count >= 50 
            && $this->visit_count >= 500;
    }

    /**
     * Marcar automáticamente como TOP si cumple criterios
     */
    public function marcarTopAutomatico(): bool
    {
        if ($this->cumpleCriteriosTop() && !$this->is_top) {
            $this->update(['is_top' => true]);
            return true;
        }
        return false;
    }
}
