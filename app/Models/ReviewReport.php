<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @OA\Schema(
 *     schema="ReviewReport",
 *     type="object",
 *     title="Reporte de Reseña",
 *     description="Reporte de una reseña inapropiada o problemática.",
 *     @OA\Property(property="id", type="integer", format="int64", description="ID único del reporte"),
 *     @OA\Property(property="review_id", type="integer", format="int64", description="ID de la reseña reportada"),
 *     @OA\Property(property="reporter_id", type="integer", format="int64", description="ID del usuario que reporta"),
 *     @OA\Property(property="reason", type="string", description="Razón del reporte"),
 *     @OA\Property(property="status", type="string", enum={"pending","resolved","dismissed"}, description="Estado del reporte"),
 *     @OA\Property(property="created_at", type="string", format="date-time", readOnly=true),
 *     @OA\Property(property="updated_at", type="string", format="date-time", readOnly=true)
 * )
 */
class ReviewReport extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'review_id',
        'reporter_id',
        'reason',
        'status',
        'admin_notes',
        'resolved_by',
        'resolved_at'
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $dates = [
        'resolved_at',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * Estados disponibles para el reporte
     */
    const STATUS_PENDING = 'pending';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_DISMISSED = 'dismissed';

    /**
     * Razones comunes de reporte
     */
    const REASONS = [
        'inappropriate_content' => 'Contenido inapropiado',
        'spam' => 'Spam o contenido no relevante',
        'fake_review' => 'Reseña falsa o engañosa',
        'harassment' => 'Acoso o comportamiento tóxico',
        'offensive_language' => 'Lenguaje ofensivo',
        'other' => 'Otra razón'
    ];

    /**
     * Get the review that is being reported.
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * Get the user who reported the review.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * Get the admin who resolved the report.
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Scope a query to only include pending reports.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope a query to only include resolved reports.
     */
    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    /**
     * Scope a query to only include dismissed reports.
     */
    public function scopeDismissed($query)
    {
        return $query->where('status', self::STATUS_DISMISSED);
    }

    /**
     * Scope a query to only include active reports (pending).
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Check if the report is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the report is resolved.
     */
    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    /**
     * Check if the report is dismissed.
     */
    public function isDismissed(): bool
    {
        return $this->status === self::STATUS_DISMISSED;
    }

    /**
     * Resolve the report.
     */
    public function resolve($adminId = null, $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolved_by' => $adminId,
            'resolved_at' => now(),
            'admin_notes' => $notes
        ]);
    }

    /**
     * Dismiss the report.
     */
    public function dismiss($adminId = null, $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_DISMISSED,
            'resolved_by' => $adminId,
            'resolved_at' => now(),
            'admin_notes' => $notes
        ]);
    }

    /**
     * Get the reason text in Spanish.
     */
    public function getReasonTextAttribute(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }

    /**
     * Get the status text in Spanish.
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_RESOLVED => 'Resuelto',
            self::STATUS_DISMISSED => 'Desestimado',
            default => $this->status
        };
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        // Al crear un reporte, verificar que no exista uno pendiente del mismo usuario para la misma reseña
        static::creating(function ($report) {
            $existingReport = static::where('review_id', $report->review_id)
                ->where('reporter_id', $report->reporter_id)
                ->where('status', self::STATUS_PENDING)
                ->first();

            if ($existingReport) {
                throw new \Exception('Ya existe un reporte pendiente para esta reseña por este usuario.');
            }
        });
    }
} 