<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="Promocion",
 *     type="object",
 *     title="Promocion",
 *     required={"id", "destino_id", "title", "start_date", "end_date", "is_active"},
 *     @OA\Property(property="id", type="integer", format="int64", description="ID de la promoción"),
 *     @OA\Property(property="destino_id", type="integer", format="int64", description="ID del destino asociado"),
 *     @OA\Property(property="title", type="string", description="Título de la promoción"),
 *     @OA\Property(property="description", type="string", nullable=true, description="Descripción de la promoción"),
 *     @OA\Property(property="code", type="string", nullable=true, description="Código de descuento"),
 *     @OA\Property(property="discount_percentage", type="number", format="float", nullable=true, description="Porcentaje de descuento"),
 *     @OA\Property(property="start_date", type="string", format="date-time", description="Fecha de inicio"),
 *     @OA\Property(property="end_date", type="string", format="date-time", description="Fecha de fin"),
 *     @OA\Property(property="is_active", type="boolean", description="Indica si la promoción está activa"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Fecha de creación"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Fecha de última actualización"),
 *     @OA\Property(
 *         property="destino",
 *         ref="#/components/schemas/Destino",
 *         description="El destino asociado a la promoción"
 *     )
 * )
 */
class Promocion extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'destino_id',
        'title',
        'description',
        'code',
        'discount_percentage',
        'start_date',
        'end_date',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the destino that owns the promocion.
     */
    public function destino(): BelongsTo
    {
        return $this->belongsTo(Destino::class);
    }
}
