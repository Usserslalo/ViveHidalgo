<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Caracteristica extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'slug',
        'tipo',
        'icono',
        'descripcion',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación muchos a muchos con Destino
     */
    public function destinos()
    {
        return $this->belongsToMany(Destino::class, 'caracteristica_destino')
                    ->withTimestamps();
    }

    /**
     * Scope para características activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para filtrar por tipo
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Boot method para generar slug automáticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($caracteristica) {
            if (empty($caracteristica->slug)) {
                $caracteristica->slug = Str::slug($caracteristica->nombre);
            }
        });

        static::updating(function ($caracteristica) {
            if ($caracteristica->isDirty('nombre') && empty($caracteristica->slug)) {
                $caracteristica->slug = Str::slug($caracteristica->nombre);
            }
        });
    }

    /**
     * Obtener el nombre formateado con icono
     */
    public function getNombreConIconoAttribute()
    {
        if ($this->icono) {
            return "<i class='{$this->icono}'></i> {$this->nombre}";
        }
        return $this->nombre;
    }
} 