<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @OA\Schema(
 *     schema="PromocionDestacada",
 *     type="object",
 *     title="Promoción Destacada",
 *     description="Promociones destacadas que aparecen en la portada del sitio.",
 *     @OA\Property(property="id", type="integer", format="int64", description="ID único de la promoción"),
 *     @OA\Property(property="titulo", type="string", description="Título de la promoción"),
 *     @OA\Property(property="descripcion", type="string", nullable=true, description="Descripción de la promoción"),
 *     @OA\Property(property="imagen", type="string", nullable=true, description="URL de la imagen de la promoción"),
 *     @OA\Property(property="fecha_inicio", type="string", format="date-time", description="Fecha de inicio de la promoción"),
 *     @OA\Property(property="fecha_fin", type="string", format="date-time", description="Fecha de fin de la promoción"),
 *     @OA\Property(property="is_active", type="boolean", description="Indica si la promoción está activa"),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly=true)
 * )
 */
class PromocionDestacada extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'promocion_destacadas';

    protected $fillable = [
        'titulo',
        'descripcion',
        'imagen',
        'fecha_inicio',
        'fecha_fin',
        'is_active',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $dates = [
        'fecha_inicio',
        'fecha_fin',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Relación muchos a muchos con destinos.
     */
    public function destinos(): BelongsToMany
    {
        return $this->belongsToMany(Destino::class, 'destino_promocion_destacada', 'promocion_destacada_id', 'destino_id')
            ->withTimestamps();
    }

    /**
     * Scope para promociones vigentes.
     */
    public function scopeVigentes($query)
    {
        $now = now();
        return $query->where('is_active', true)
            ->where('fecha_inicio', '<=', $now)
            ->where('fecha_fin', '>=', $now);
    }

    /**
     * Scope para promociones futuras.
     */
    public function scopeFuturas($query)
    {
        return $query->where('is_active', true)
            ->where('fecha_inicio', '>', now());
    }

    /**
     * Scope para promociones expiradas.
     */
    public function scopeExpiradas($query)
    {
        return $query->where('fecha_fin', '<', now());
    }

    /**
     * Saber si la promoción está vigente.
     */
    public function getEstaVigenteAttribute(): bool
    {
        $now = now();
        return $this->is_active && $this->fecha_inicio <= $now && $this->fecha_fin >= $now;
    }

    /**
     * Saber si la promoción es futura.
     */
    public function getEsFuturaAttribute(): bool
    {
        return $this->is_active && $this->fecha_inicio > now();
    }

    /**
     * Saber si la promoción está expirada.
     */
    public function getEstaExpiradaAttribute(): bool
    {
        return $this->fecha_fin < now();
    }

    /**
     * Obtener el estado de la promoción.
     */
    public function getEstadoAttribute(): string
    {
        if ($this->esta_vigente) {
            return 'vigente';
        } elseif ($this->es_futura) {
            return 'futura';
        } else {
            return 'expirada';
        }
    }

    /**
     * Boot method para eventos del modelo.
     */
    protected static function boot()
    {
        parent::boot();

        // Evento antes de guardar
        static::saving(function ($promocion) {
            // Validar que fecha_fin sea mayor que fecha_inicio
            if ($promocion->fecha_fin <= $promocion->fecha_inicio) {
                throw new \InvalidArgumentException('La fecha de fin debe ser mayor que la fecha de inicio.');
            }
        });
    }
}