<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @OA\Schema(
 *     schema="HomeConfig",
 *     type="object",
 *     title="Configuración de Portada",
 *     description="Configuración editable de la portada del sitio web.",
 *     @OA\Property(property="id", type="integer", format="int64", description="ID único de la configuración"),
 *     @OA\Property(property="hero_image_path", type="string", nullable=true, description="Ruta de la imagen de fondo del hero"),
 *     @OA\Property(property="hero_title", type="string", nullable=true, description="Título principal del hero"),
 *     @OA\Property(property="hero_subtitle", type="string", nullable=true, description="Subtítulo del hero"),
 *     @OA\Property(property="search_placeholder", type="string", nullable=true, description="Texto placeholder del buscador"),
 *     @OA\Property(property="featured_sections", type="object", description="Secciones destacadas en formato JSON"),
 *     @OA\Property(property="is_active", type="boolean", description="Indica si esta configuración está activa"),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly=true)
 * )
 */
class HomeConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'hero_image_path',
        'hero_title',
        'hero_subtitle',
        'search_placeholder',
        'featured_sections',
        'is_active',
    ];

    protected $casts = [
        'featured_sections' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Relación polimórfica con imágenes
     */
    public function imagenes(): MorphMany
    {
        return $this->morphMany(Imagen::class, 'imageable')->ordered();
    }

    /**
     * Obtener la imagen principal del hero
     */
    public function heroImagen()
    {
        return $this->morphOne(Imagen::class, 'imageable')->where('is_main', true);
    }

    /**
     * Scope para configuraciones activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Obtener la configuración activa (solo una debe estar activa)
     */
    public static function getActive()
    {
        return static::active()->first();
    }

    /**
     * Activar esta configuración y desactivar las demás
     */
    public function activate()
    {
        // Desactivar todas las configuraciones
        static::query()->update(['is_active' => false]);
        
        // Activar esta
        $this->update(['is_active' => true]);
    }

    /**
     * Obtener las secciones destacadas formateadas
     */
    public function getFeaturedSectionsFormatted()
    {
        if (!$this->featured_sections) {
            return [];
        }

        return collect($this->featured_sections)->map(function ($section) {
            // Contar destinos asignados
            $destinationsCount = 0;
            if (!empty($section['destino_ids'])) {
                $destinationsCount = \App\Models\Destino::whereIn('id', $section['destino_ids'])
                    ->where('status', 'published')
                    ->count();
            }

            return [
                'slug' => $section['slug'] ?? '',
                'title' => $section['title'] ?? '',
                'subtitle' => $section['subtitle'] ?? '',
                'image' => $section['image'] ?? null,
                'destino_ids' => $section['destino_ids'] ?? [],
                'destinations_count' => $destinationsCount,
                'order' => $section['order'] ?? 0,
                'accent_color' => $section['accent_color'] ?? null,
                'metadata' => $section['metadata'] ?? [],
            ];
        })->sortBy('order')->values()->toArray();
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        // Al crear una nueva configuración activa, desactivar las demás
        static::creating(function ($config) {
            if ($config->is_active) {
                static::query()->update(['is_active' => false]);
            }
        });

        // Al actualizar una configuración como activa, desactivar las demás
        static::updating(function ($config) {
            if ($config->isDirty('is_active') && $config->is_active) {
                static::where('id', '!=', $config->id)->update(['is_active' => false]);
            }
        });
    }
} 