<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends BaseController
{
    /**
     * Obtener las notificaciones del usuario autenticado
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $perPage = $request->get('per_page', 15);
            $unreadOnly = $request->boolean('unread_only', false);

            $query = $user->notifications();

            if ($unreadOnly) {
                $query->unread();
            }

            $notifications = $query->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return $this->sendResponse([
                'notifications' => $notifications->items(),
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                ],
                'unread_count' => $user->unreadNotifications()->count(),
            ], 'Notificaciones obtenidas exitosamente');

        } catch (\Exception $e) {
            return $this->sendError('Error al obtener notificaciones', $e->getMessage());
        }
    }

    /**
     * Marcar una notificación como leída
     */
    public function markAsRead(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $notification = $user->notifications()->findOrFail($id);
            
            $notification->markAsRead();

            return $this->sendResponse([
                'notification' => $notification,
                'unread_count' => $user->unreadNotifications()->count(),
            ], 'Notificación marcada como leída');

        } catch (\Exception $e) {
            return $this->sendError('Error al marcar notificación como leída', $e->getMessage());
        }
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function markAllAsRead(): JsonResponse
    {
        try {
            $user = Auth::user();
            $user->unreadNotifications()->update(['read_at' => now()]);

            return $this->sendResponse([
                'unread_count' => 0,
            ], 'Todas las notificaciones marcadas como leídas');

        } catch (\Exception $e) {
            return $this->sendError('Error al marcar notificaciones como leídas', $e->getMessage());
        }
    }

    /**
     * Eliminar una notificación
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $notification = $user->notifications()->findOrFail($id);
            
            $notification->delete();

            return $this->sendResponse([
                'unread_count' => $user->unreadNotifications()->count(),
            ], 'Notificación eliminada exitosamente');

        } catch (\Exception $e) {
            return $this->sendError('Error al eliminar notificación', $e->getMessage());
        }
    }

    /**
     * Obtener estadísticas de notificaciones
     */
    public function stats(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $stats = [
                'total' => $user->notifications()->count(),
                'unread' => $user->unreadNotifications()->count(),
                'read' => $user->readNotifications()->count(),
                'recent' => $user->notifications()
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count(),
            ];

            return $this->sendResponse($stats, 'Estadísticas obtenidas exitosamente');

        } catch (\Exception $e) {
            return $this->sendError('Error al obtener estadísticas', $e->getMessage());
        }
    }
} 