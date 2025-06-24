<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Schema(
 *     schema="Imagen",
 *     type="object",
 *     title="Imagen",
 *     required={"id", "path", "imageable_type", "imageable_id"},
 *     @OA\Property(property="id", type="integer", format="int64", description="ID único de la imagen"),
 *     @OA\Property(property="path", type="string", description="Ruta del archivo de imagen"),
 *     @OA\Property(property="alt", type="string", nullable=true, description="Texto alternativo de la imagen"),
 *     @OA\Property(property="orden", type="integer", description="Orden de la imagen"),
 *     @OA\Property(property="is_main", type="boolean", description="Indica si es la imagen principal"),
 *     @OA\Property(property="url", type="string", description="URL completa de la imagen"),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly=true)
 * )
 */
class Imagen extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla
     */
    protected $table = 'imagenes';

    protected $fillable = [
        'path',
        'alt',
        'orden',
        'is_main',
        'disk',
        'mime_type',
        'size',
    ];

    protected $casts = [
        'is_main' => 'boolean',
        'orden' => 'integer',
        'size' => 'integer',
    ];

    protected $appends = ['url'];

    /**
     * Relación polimórfica - la imagen pertenece a cualquier modelo
     */
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Accessor para obtener la URL completa de la imagen
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Scope para obtener solo imágenes principales
     */
    public function scopeMain($query)
    {
        return $query->where('is_main', true);
    }

    /**
     * Scope para ordenar por orden
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('orden', 'asc');
    }

    /**
     * Boot method para manejar la imagen principal
     */
    /*
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($imagen) {
            // Si esta imagen es principal, quitar la principal de otras imágenes del mismo modelo
            if ($imagen->is_main) {
                static::where('imageable_type', $imagen->imageable_type)
                      ->where('imageable_id', $imagen->imageable_id)
                      ->where('is_main', true)
                      ->update(['is_main' => false]);
            }
        });

        static::updating(function ($imagen) {
            // Si esta imagen se está marcando como principal, quitar la principal de otras
            if ($imagen->isDirty('is_main') && $imagen->is_main) {
                static::where('imageable_type', $imagen->imageable_type)
                      ->where('imageable_id', $imagen->imageable_id)
                      ->where('id', '!=', $imagen->id)
                      ->where('is_main', true)
                      ->update(['is_main' => false]);
            }
        });

        static::deleting(function ($imagen) {
            // Eliminar el archivo físico cuando se elimina el registro
            if (Storage::disk($imagen->disk)->exists($imagen->path)) {
                Storage::disk($imagen->disk)->delete($imagen->path);
            }
        });
    }
    */
} 