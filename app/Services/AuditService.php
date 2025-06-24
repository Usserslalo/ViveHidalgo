<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Registrar un evento de auditoría
     */
    public static function log(
        string $eventType,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null,
        ?array $metadata = null,
        ?Request $request = null
    ): AuditLog {
        $request = $request ?? request();
        
        return AuditLog::create([
            'user_id' => Auth::id(),
            'event_type' => $eventType,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id' => $auditable ? $auditable->id : null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Registrar evento de creación
     */
    public static function logCreated(Model $model, ?Request $request = null): AuditLog
    {
        return self::log(
            AuditLog::EVENT_CREATED,
            $model,
            null,
            $model->getAttributes(),
            "Creó " . class_basename($model),
            null,
            $request
        );
    }

    /**
     * Registrar evento de actualización
     */
    public static function logUpdated(Model $model, array $oldValues, array $newValues, ?Request $request = null): AuditLog
    {
        return self::log(
            AuditLog::EVENT_UPDATED,
            $model,
            $oldValues,
            $newValues,
            "Actualizó " . class_basename($model),
            null,
            $request
        );
    }

    /**
     * Registrar evento de eliminación
     */
    public static function logDeleted(Model $model, ?Request $request = null): AuditLog
    {
        return self::log(
            AuditLog::EVENT_DELETED,
            $model,
            $model->getAttributes(),
            null,
            "Eliminó " . class_basename($model),
            null,
            $request
        );
    }

    /**
     * Registrar evento de login
     */
    public static function logLogin(Model $user, ?Request $request = null): AuditLog
    {
        return self::log(
            AuditLog::EVENT_LOGIN,
            $user,
            null,
            null,
            "Usuario inició sesión",
            [
                'session_id' => session()->getId(),
                'device_type' => self::detectDeviceType($request),
            ],
            $request
        );
    }

    /**
     * Registrar evento de logout
     */
    public static function logLogout(Model $user, ?Request $request = null): AuditLog
    {
        return self::log(
            AuditLog::EVENT_LOGOUT,
            $user,
            null,
            null,
            "Usuario cerró sesión",
            [
                'session_id' => session()->getId(),
                'device_type' => self::detectDeviceType($request),
            ],
            $request
        );
    }

    /**
     * Registrar evento de suscripción creada
     */
    public static function logSubscriptionCreated(Model $subscription, ?Request $request = null): AuditLog
    {
        return self::log(
            AuditLog::EVENT_SUBSCRIPTION_CREATED,
            $subscription,
            null,
            $subscription->getAttributes(),
            "Creó suscripción",
            [
                'plan_type' => $subscription->plan_type,
                'amount' => $subscription->amount,
                'billing_cycle' => $subscription->billing_cycle,
            ],
            $request
        );
    }

    /**
     * Registrar evento de suscripción cancelada
     */
    public static function logSubscriptionCancelled(Model $subscription, ?Request $request = null): AuditLog
    {
        return self::log(
            AuditLog::EVENT_SUBSCRIPTION_CANCELLED,
            $subscription,
            $subscription->getAttributes(),
            null,
            "Canceló suscripción",
            [
                'plan_type' => $subscription->plan_type,
                'cancellation_reason' => 'user_request',
            ],
            $request
        );
    }

    /**
     * Registrar evento de reseña aprobada
     */
    public static function logReviewApproved(Model $review, ?Request $request = null): AuditLog
    {
        return self::log(
            AuditLog::EVENT_REVIEW_APPROVED,
            $review,
            ['is_approved' => false],
            ['is_approved' => true],
            "Aprobó reseña",
            [
                'rating' => $review->rating,
                'destino_id' => $review->destino_id,
            ],
            $request
        );
    }

    /**
     * Registrar evento de reseña rechazada
     */
    public static function logReviewRejected(Model $review, ?Request $request = null): AuditLog
    {
        return self::log(
            AuditLog::EVENT_REVIEW_REJECTED,
            $review,
            ['is_approved' => false],
            ['is_approved' => false],
            "Rechazó reseña",
            [
                'rating' => $review->rating,
                'destino_id' => $review->destino_id,
            ],
            $request
        );
    }

    /**
     * Registrar evento de promoción expirada
     */
    public static function logPromotionExpired(Model $promotion, ?Request $request = null): AuditLog
    {
        return self::log(
            AuditLog::EVENT_PROMOTION_EXPIRED,
            $promotion,
            ['is_active' => true],
            ['is_active' => false],
            "Promoción expirada automáticamente",
            [
                'title' => $promotion->title,
                'end_date' => $promotion->end_date,
            ],
            $request
        );
    }

    /**
     * Detectar tipo de dispositivo
     */
    private static function detectDeviceType(?Request $request): string
    {
        if (!$request) {
            return 'unknown';
        }

        $userAgent = $request->userAgent();
        
        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i', strtolower($userAgent))) {
            return 'tablet';
        }
        
        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i', strtolower($userAgent))) {
            return 'mobile';
        }
        
        return 'desktop';
    }

    /**
     * Obtener estadísticas de auditoría
     */
    public static function getStats(int $days = 30): array
    {
        return [
            'total_logs' => AuditLog::recent($days)->count(),
            'events_by_type' => AuditLog::getEventStats($days),
            'top_users' => AuditLog::getUserActivityStats($days),
            'recent_activity' => AuditLog::recent($days)
                ->with(['user:id,name', 'auditable'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'event_type' => $log->event_type,
                        'event_description' => $log->event_description,
                        'user_name' => $log->user_name,
                        'auditable_name' => $log->auditable_name,
                        'description' => $log->description,
                        'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
        ];
    }

    /**
     * Limpiar logs antiguos
     */
    public static function cleanOldLogs(int $daysToKeep = 90): int
    {
        return AuditLog::where('created_at', '<', now()->subDays($daysToKeep))->delete();
    }
} 