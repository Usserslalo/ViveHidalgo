<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="AuditLog",
 *     type="object",
 *     title="Audit Log",
 *     required={"id", "event_type", "user_id", "created_at"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="event_type", type="string", example="created"),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="auditable_type", type="string", example="App\\Models\\User"),
 *     @OA\Property(property="auditable_id", type="integer", example=1),
 *     @OA\Property(property="description", type="string", example="Creó un destino"),
 *     @OA\Property(property="ip_address", type="string", example="127.0.0.1"),
 *     @OA\Property(property="user_agent", type="string", example="Mozilla/5.0"),
 *     @OA\Property(property="url", type="string", example="/api/v1/destinos"),
 *     @OA\Property(property="method", type="string", example="POST"),
 *     @OA\Property(property="old_values", type="object"),
 *     @OA\Property(property="new_values", type="object"),
 *     @OA\Property(property="metadata", type="object"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-06-24T12:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-06-24T12:00:00Z")
 * )
 */

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_type',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'description',
        'metadata',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
    ];

    // Event types constants
    const EVENT_CREATED = 'created';
    const EVENT_UPDATED = 'updated';
    const EVENT_DELETED = 'deleted';
    const EVENT_LOGIN = 'login';
    const EVENT_LOGOUT = 'logout';
    const EVENT_SUBSCRIPTION_CREATED = 'subscription_created';
    const EVENT_SUBSCRIPTION_CANCELLED = 'subscription_cancelled';
    const EVENT_REVIEW_APPROVED = 'review_approved';
    const EVENT_REVIEW_REJECTED = 'review_rejected';
    const EVENT_PROMOTION_EXPIRED = 'promotion_expired';

    /**
     * Relación con el usuario que realizó la acción
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación polimórfica con el modelo auditado
     */
    public function auditable()
    {
        return $this->morphTo();
    }

    /**
     * Scope para filtrar por tipo de evento
     */
    public function scopeEventType($query, $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope para filtrar por usuario
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para filtrar por modelo auditado
     */
    public function scopeForModel($query, $modelType, $modelId = null)
    {
        $query->where('auditable_type', $modelType);
        
        if ($modelId) {
            $query->where('auditable_id', $modelId);
        }
        
        return $query;
    }

    /**
     * Scope para filtrar por rango de fechas
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope para obtener logs recientes
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Obtener el nombre del usuario que realizó la acción
     */
    public function getUserNameAttribute(): string
    {
        return $this->user ? $this->user->name : 'Sistema';
    }

    /**
     * Obtener el nombre del modelo auditado
     */
    public function getAuditableNameAttribute(): string
    {
        if (!$this->auditable) {
            return 'Modelo eliminado';
        }

        return match ($this->auditable_type) {
            User::class => $this->auditable->name,
            Destino::class => $this->auditable->name,
            Promocion::class => $this->auditable->title,
            Review::class => "Reseña #{$this->auditable_id}",
            Subscription::class => "Suscripción #{$this->auditable_id}",
            default => class_basename($this->auditable_type) . " #{$this->auditable_id}",
        };
    }

    /**
     * Obtener descripción legible del evento
     */
    public function getEventDescriptionAttribute(): string
    {
        return match ($this->event_type) {
            self::EVENT_CREATED => 'Creó',
            self::EVENT_UPDATED => 'Actualizó',
            self::EVENT_DELETED => 'Eliminó',
            self::EVENT_LOGIN => 'Inició sesión',
            self::EVENT_LOGOUT => 'Cerró sesión',
            self::EVENT_SUBSCRIPTION_CREATED => 'Creó suscripción',
            self::EVENT_SUBSCRIPTION_CANCELLED => 'Canceló suscripción',
            self::EVENT_REVIEW_APPROVED => 'Aprobó reseña',
            self::EVENT_REVIEW_REJECTED => 'Rechazó reseña',
            self::EVENT_PROMOTION_EXPIRED => 'Promoción expirada',
            default => $this->event_type,
        };
    }

    /**
     * Obtener cambios formateados para mostrar
     */
    public function getChangesAttribute(): array
    {
        if (!$this->old_values || !$this->new_values) {
            return [];
        }

        $changes = [];
        foreach ($this->new_values as $field => $newValue) {
            $oldValue = $this->old_values[$field] ?? null;
            if ($oldValue !== $newValue) {
                $changes[$field] = [
                    'from' => $oldValue,
                    'to' => $newValue,
                ];
            }
        }

        return $changes;
    }

    /**
     * Verificar si el log tiene cambios
     */
    public function hasAuditChanges(): bool
    {
        return !empty($this->changes);
    }

    /**
     * Obtener estadísticas de eventos por tipo
     */
    public static function getEventStats($days = 30): array
    {
        return self::recent($days)
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->pluck('count', 'event_type')
            ->toArray();
    }

    /**
     * Obtener estadísticas de actividad por usuario
     */
    public static function getUserActivityStats($days = 30): array
    {
        return self::recent($days)
            ->with('user:id,name')
            ->selectRaw('user_id, COUNT(*) as activity_count')
            ->groupBy('user_id')
            ->orderByDesc('activity_count')
            ->limit(10)
            ->get()
            ->map(function ($log) {
                return [
                    'user_id' => $log->user_id,
                    'user_name' => $log->user ? $log->user->name : 'Sistema',
                    'activity_count' => $log->activity_count,
                ];
            })
            ->toArray();
    }
}
