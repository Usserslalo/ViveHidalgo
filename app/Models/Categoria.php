<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Categoria extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
    ];

    /**
     * The destinos that belong to the Categoria.
     */
    public function destinos(): BelongsToMany
    {
        return $this->belongsToMany(Destino::class, 'categoria_destino');
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($categoria) {
            if (empty($categoria->slug)) {
                $categoria->slug = Str::slug($categoria->name);
            }
        });

        static::updating(function ($categoria) {
            if ($categoria->isDirty('name') && empty($categoria->slug)) {
                $categoria->slug = Str::slug($categoria->name);
            }
        });
    }
}
