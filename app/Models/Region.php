<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class Region extends Model
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
            'description' => $this->description,
        ];
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Get all of the destinos for the Region.
     */
    public function destinos(): HasMany
    {
        return $this->hasMany(Destino::class);
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
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($region) {
            if (empty($region->slug)) {
                $region->slug = Str::slug($region->name);
            }
        });

        static::updating(function ($region) {
            if ($region->isDirty('name') && empty($region->slug)) {
                $region->slug = Str::slug($region->name);
            }
        });
    }
}
