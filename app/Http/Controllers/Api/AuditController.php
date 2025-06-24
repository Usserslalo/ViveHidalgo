<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Audit",
 *     description="API Endpoints para auditoría y analíticas"
 * )
 */
class AuditController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/v1/audit/logs",
     *     operationId="getAuditLogs",
     *     tags={"Audit"},
     *     summary="Obtener logs de auditoría",
     *     description="Retorna los logs de auditoría con filtros y paginación",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="event_type",
     *         in="query",
     *         description="Filtrar por tipo de evento",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="Filtrar por usuario",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="auditable_type",
     *         in="query",
     *         description="Filtrar por tipo de modelo auditado",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Fecha de inicio (Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="Fecha de fin (Y-m-d)",
     *         required=false,
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Elementos por página",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Logs obtenidos exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logs obtenidos exitosamente"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="logs", type="array", @OA\Items(ref="#/components/schemas/AuditLog")),
     *                 @OA\Property(property="pagination", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado"
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Verificar permisos de administrador
            if (!$request->user()->isAdmin()) {
                return $this->sendError('Acceso denegado', 'Solo los administradores pueden acceder a los logs de auditoría', 403);
            }

            $query = AuditLog::with(['user:id,name', 'auditable']);

            // Aplicar filtros
            if ($request->filled('event_type')) {
                $query->eventType($request->event_type);
            }

            if ($request->filled('user_id')) {
                $query->forUser($request->user_id);
            }

            if ($request->filled('auditable_type')) {
                $query->forModel($request->auditable_type);
            }

            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->dateRange($request->start_date, $request->end_date);
            }

            $perPage = $request->get('per_page', 15);
            $logs = $query->orderByDesc('created_at')->paginate($perPage);

            // Formatear datos para la respuesta
            $formattedLogs = $logs->getCollection()->map(function ($log) {
                return [
                    'id' => $log->id,
                    'event_type' => $log->event_type,
                    'event_description' => $log->event_description,
                    'user_name' => $log->user_name,
                    'auditable_name' => $log->auditable_name,
                    'description' => $log->description,
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'url' => $log->url,
                    'method' => $log->method,
                    'changes' => $log->changes,
                    'metadata' => $log->metadata,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return $this->sendResponse([
                'logs' => $formattedLogs,
                'pagination' => [
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                ],
            ], 'Logs obtenidos exitosamente');

        } catch (\Exception $e) {
            return $this->sendError('Error al obtener logs', $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/audit/stats",
     *     operationId="getAuditStats",
     *     tags={"Audit"},
     *     summary="Obtener estadísticas de auditoría",
     *     description="Retorna estadísticas y métricas de auditoría",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         description="Número de días para las estadísticas",
     *         required=false,
     *         @OA\Schema(type="integer", default=30)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Estadísticas obtenidas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Estadísticas obtenidas exitosamente"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_logs", type="integer"),
     *                 @OA\Property(property="events_by_type", type="object"),
     *                 @OA\Property(
     *                     property="top_users",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="user_id", type="integer", example=1),
     *                         @OA\Property(property="user_name", type="string", example="Juan Pérez"),
     *                         @OA\Property(property="activity_count", type="integer", example=5)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="recent_activity",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="event_type", type="string", example="created"),
     *                         @OA\Property(property="event_description", type="string", example="Creó"),
     *                         @OA\Property(property="user_name", type="string", example="Juan Pérez"),
     *                         @OA\Property(property="auditable_name", type="string", example="Hotel Real"),
     *                         @OA\Property(property="description", type="string", example="Creó un destino"),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2024-06-24 12:00:00")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado"
     *     )
     * )
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            // Verificar permisos de administrador
            if (!$request->user()->isAdmin()) {
                return $this->sendError('Acceso denegado', 'Solo los administradores pueden acceder a las estadísticas', 403);
            }

            $days = $request->get('days', 30);
            $stats = AuditService::getStats($days);

            return $this->sendResponse($stats, 'Estadísticas obtenidas exitosamente');

        } catch (\Exception $e) {
            return $this->sendError('Error al obtener estadísticas', $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/audit/logs/{id}",
     *     operationId="getAuditLog",
     *     tags={"Audit"},
     *     summary="Obtener log de auditoría específico",
     *     description="Retorna un log de auditoría específico con todos sus detalles",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del log de auditoría",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Log obtenido exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Log obtenido exitosamente"),
     *             @OA\Property(property="data", ref="#/components/schemas/AuditLog")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Log no encontrado"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado"
     *     )
     * )
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            // Verificar permisos de administrador
            if (!$request->user()->isAdmin()) {
                return $this->sendError('Acceso denegado', 'Solo los administradores pueden acceder a los logs de auditoría', 403);
            }

            $log = AuditLog::with(['user:id,name', 'auditable'])->findOrFail($id);

            $data = [
                'id' => $log->id,
                'event_type' => $log->event_type,
                'event_description' => $log->event_description,
                'user_name' => $log->user_name,
                'auditable_name' => $log->auditable_name,
                'description' => $log->description,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'url' => $log->url,
                'method' => $log->method,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'changes' => $log->changes,
                'metadata' => $log->metadata,
                'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $log->updated_at->format('Y-m-d H:i:s'),
            ];

            return $this->sendResponse($data, 'Log obtenido exitosamente');

        } catch (\Exception $e) {
            return $this->sendError('Error al obtener log', $e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/audit/logs/clean",
     *     operationId="cleanAuditLogs",
     *     tags={"Audit"},
     *     summary="Limpiar logs antiguos",
     *     description="Elimina logs de auditoría más antiguos que el número de días especificado",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="days_to_keep",
     *         in="query",
     *         description="Número de días a mantener",
     *         required=false,
     *         @OA\Schema(type="integer", default=90)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Logs limpiados exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logs limpiados exitosamente"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="deleted_count", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado"
     *     )
     * )
     */
    public function clean(Request $request): JsonResponse
    {
        try {
            // Verificar permisos de administrador
            if (!$request->user()->isAdmin()) {
                return $this->sendError('Acceso denegado', 'Solo los administradores pueden limpiar logs', 403);
            }

            $daysToKeep = $request->get('days_to_keep', 90);
            $deletedCount = AuditService::cleanOldLogs($daysToKeep);

            return $this->sendResponse([
                'deleted_count' => $deletedCount,
            ], "Se eliminaron {$deletedCount} logs antiguos");

        } catch (\Exception $e) {
            return $this->sendError('Error al limpiar logs', $e->getMessage());
        }
    }
} 