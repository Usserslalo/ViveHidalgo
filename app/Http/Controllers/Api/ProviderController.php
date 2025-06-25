<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Destino;
use App\Models\Promocion;
use App\Models\Review;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Provider Dashboard",
 *     description="Endpoints para el dashboard de proveedores"
 * )
 */
class ProviderController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/v1/provider/dashboard",
     *     operationId="getProviderDashboard",
     *     tags={"Provider Dashboard"},
     *     summary="Dashboard del proveedor",
     *     description="Estadísticas y métricas detalladas del proveedor autenticado",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard recuperado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="overview", type="object",
     *                     @OA\Property(property="total_destinos", type="integer", example=15),
     *                     @OA\Property(property="destinos_activos", type="integer", example=12),
     *                     @OA\Property(property="destinos_pendientes", type="integer", example=2),
     *                     @OA\Property(property="destinos_borrador", type="integer", example=1),
     *                     @OA\Property(property="total_promociones", type="integer", example=8),
     *                     @OA\Property(property="promociones_activas", type="integer", example=5),
     *                     @OA\Property(property="promociones_expiradas", type="integer", example=3),
     *                     @OA\Property(property="total_reviews", type="integer", example=45),
     *                     @OA\Property(property="reviews_pendientes", type="integer", example=3),
     *                     @OA\Property(property="rating_promedio", type="number", format="float", example=4.2),
     *                     @OA\Property(property="total_favoritos", type="integer", example=127),
     *                     @OA\Property(property="total_visitas", type="integer", example=2340)
     *                 ),
     *                 @OA\Property(property="recent_activity", type="object",
     *                     @OA\Property(property="recent_reviews", type="array", @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="destino_name", type="string", example="Balneario El Tephé"),
     *                         @OA\Property(property="rating", type="integer", example=5),
     *                         @OA\Property(property="comment", type="string", example="Excelente lugar para la familia"),
     *                         @OA\Property(property="created_at", type="string", format="date-time")
     *                     )),
     *                     @OA\Property(property="recent_favorites", type="array", @OA\Items(
     *                         @OA\Property(property="destino_id", type="integer", example=1),
     *                         @OA\Property(property="destino_name", type="string", example="Balneario El Tephé"),
     *                         @OA\Property(property="user_name", type="string", example="Juan Pérez"),
     *                         @OA\Property(property="created_at", type="string", format="date-time")
     *                     ))
     *                 ),
     *                 @OA\Property(property="performance_metrics", type="object",
     *                     @OA\Property(property="destinos_por_region", type="array", @OA\Items(
     *                         @OA\Property(property="region", type="string", example="Valle del Mezquital"),
     *                         @OA\Property(property="count", type="integer", example=8),
     *                         @OA\Property(property="percentage", type="number", format="float", example=53.3)
     *                     )),
     *                     @OA\Property(property="destinos_por_categoria", type="array", @OA\Items(
     *                         @OA\Property(property="categoria", type="string", example="Balneario"),
     *                         @OA\Property(property="count", type="integer", example=6),
     *                         @OA\Property(property="percentage", type="number", format="float", example=40.0)
     *                     )),
     *                     @OA\Property(property="rating_distribution", type="array", @OA\Items(
     *                         @OA\Property(property="rating", type="integer", example=5),
     *                         @OA\Property(property="count", type="integer", example=25),
     *                         @OA\Property(property="percentage", type="number", format="float", example=55.6)
     *                     ))
     *                 ),
     *                 @OA\Property(property="subscription_info", type="object",
     *                     @OA\Property(property="current_plan", type="string", example="Premium"),
     *                     @OA\Property(property="status", type="string", example="active"),
     *                     @OA\Property(property="expires_at", type="string", format="date-time"),
     *                     @OA\Property(property="features", type="array", @OA\Items(type="string"))
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Dashboard del proveedor recuperado exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No autorizado.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado - Solo para proveedores",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Acceso denegado. Solo para proveedores.")
     *         )
     *     )
     * )
     */
    public function dashboard(Request $request): JsonResponse
    {
        // Verificar que el usuario sea un proveedor
        $user = $request->user();
        if (!$user->isProvider()) {
            return $this->errorResponse('Acceso denegado. Solo para proveedores.', 403);
        }

        return $this->getCachedData("provider_dashboard_{$user->id}", function () use ($user) {
            return $this->generateDashboardData($user);
        }, 300); // Cache por 5 minutos
    }

    /**
     * Generar datos del dashboard
     */
    private function generateDashboardData($user): JsonResponse
    {
        // Estadísticas generales
        $overview = $this->getOverviewStats($user);
        
        // Actividad reciente
        $recentActivity = $this->getRecentActivity($user);
        
        // Métricas de rendimiento
        $performanceMetrics = $this->getPerformanceMetrics($user);
        
        // Información de suscripción
        $subscriptionInfo = $this->getSubscriptionInfo($user);

        $dashboardData = [
            'overview' => $overview,
            'recent_activity' => $recentActivity,
            'performance_metrics' => $performanceMetrics,
            'subscription_info' => $subscriptionInfo,
        ];

        return $this->successResponse($dashboardData, 'Dashboard del proveedor recuperado exitosamente.');
    }

    /**
     * Obtener estadísticas generales
     */
    private function getOverviewStats($user): array
    {
        // Estadísticas de destinos
        $destinosStats = Destino::where('user_id', $user->id)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "published" THEN 1 ELSE 0 END) as activos,
                SUM(CASE WHEN status = "pending_review" THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN status = "draft" THEN 1 ELSE 0 END) as borrador
            ')
            ->first();

        // Estadísticas de promociones
        $promocionesStats = Promocion::whereHas('destino', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN fecha_fin >= NOW() THEN 1 ELSE 0 END) as activas,
            SUM(CASE WHEN fecha_fin < NOW() THEN 1 ELSE 0 END) as expiradas
        ')
        ->first();

        // Estadísticas de reseñas
        $reviewsStats = Review::whereHas('destino', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pendientes,
            AVG(rating) as rating_promedio
        ')
        ->first();

        // Total de favoritos
        $totalFavoritos = DB::table('favoritos')
            ->join('destinos', 'favoritos.destino_id', '=', 'destinos.id')
            ->where('destinos.user_id', $user->id)
            ->count();

        // Total de visitas (simulado por ahora)
        $totalVisitas = rand(1000, 5000); // Esto se implementaría con un sistema de tracking real

        return [
            'total_destinos' => $destinosStats->total ?? 0,
            'destinos_activos' => $destinosStats->activos ?? 0,
            'destinos_pendientes' => $destinosStats->pendientes ?? 0,
            'destinos_borrador' => $destinosStats->borrador ?? 0,
            'total_promociones' => $promocionesStats->total ?? 0,
            'promociones_activas' => $promocionesStats->activas ?? 0,
            'promociones_expiradas' => $promocionesStats->expiradas ?? 0,
            'total_reviews' => $reviewsStats->total ?? 0,
            'reviews_pendientes' => $reviewsStats->pendientes ?? 0,
            'rating_promedio' => round($reviewsStats->rating_promedio ?? 0, 1),
            'total_favoritos' => $totalFavoritos,
            'total_visitas' => $totalVisitas,
        ];
    }

    /**
     * Obtener actividad reciente
     */
    private function getRecentActivity($user): array
    {
        // Reseñas recientes
        $recentReviews = Review::with(['destino:id,name'])
            ->whereHas('destino', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('status', 'approved')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'destino_name' => $review->destino->name,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at,
                ];
            });

        // Favoritos recientes
        $recentFavorites = DB::table('favoritos')
            ->join('destinos', 'favoritos.destino_id', '=', 'destinos.id')
            ->join('users', 'favoritos.user_id', '=', 'users.id')
            ->where('destinos.user_id', $user->id)
            ->select('destinos.id as destino_id', 'destinos.name as destino_name', 'users.name as user_name', 'favoritos.created_at')
            ->orderBy('favoritos.created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($favorite) {
                return [
                    'destino_id' => $favorite->destino_id,
                    'destino_name' => $favorite->destino_name,
                    'user_name' => $favorite->user_name,
                    'created_at' => $favorite->created_at,
                ];
            });

        return [
            'recent_reviews' => $recentReviews,
            'recent_favorites' => $recentFavorites,
        ];
    }

    /**
     * Obtener métricas de rendimiento
     */
    private function getPerformanceMetrics($user): array
    {
        // Destinos por región
        $destinosPorRegion = Destino::with('region:id,name')
            ->where('user_id', $user->id)
            ->where('status', 'published')
            ->get()
            ->groupBy('region.name')
            ->map(function ($destinos, $regionName) {
                return [
                    'region' => $regionName,
                    'count' => $destinos->count(),
                    'percentage' => 0, // Se calculará después
                ];
            })
            ->values();

        // Calcular porcentajes para regiones
        $totalDestinos = $destinosPorRegion->sum('count');
        $destinosPorRegion = $destinosPorRegion->map(function ($item) use ($totalDestinos) {
            $item['percentage'] = $totalDestinos > 0 ? round(($item['count'] / $totalDestinos) * 100, 1) : 0;
            return $item;
        });

        // Destinos por categoría
        $destinosPorCategoria = DB::table('destinos')
            ->join('categoria_destino', 'destinos.id', '=', 'categoria_destino.destino_id')
            ->join('categorias', 'categoria_destino.categoria_id', '=', 'categorias.id')
            ->where('destinos.user_id', $user->id)
            ->where('destinos.status', 'published')
            ->select('categorias.name as categoria', DB::raw('COUNT(*) as count'))
            ->groupBy('categorias.id', 'categorias.name')
            ->get()
            ->map(function ($item) use ($totalDestinos) {
                return [
                    'categoria' => $item->categoria,
                    'count' => $item->count,
                    'percentage' => $totalDestinos > 0 ? round(($item->count / $totalDestinos) * 100, 1) : 0,
                ];
            });

        // Distribución de ratings
        $ratingDistribution = Review::whereHas('destino', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->where('status', 'approved')
        ->selectRaw('rating, COUNT(*) as count')
        ->groupBy('rating')
        ->orderBy('rating', 'desc')
        ->get()
        ->map(function ($item) {
            $totalReviews = Review::whereHas('destino', function ($query) {
                $query->where('user_id', auth()->id());
            })->where('status', 'approved')->count();
            
            return [
                'rating' => $item->rating,
                'count' => $item->count,
                'percentage' => $totalReviews > 0 ? round(($item->count / $totalReviews) * 100, 1) : 0,
            ];
        });

        return [
            'destinos_por_region' => $destinosPorRegion,
            'destinos_por_categoria' => $destinosPorCategoria,
            'rating_distribution' => $ratingDistribution,
        ];
    }

    /**
     * Obtener información de suscripción
     */
    private function getSubscriptionInfo($user): array
    {
        $subscription = Subscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        if (!$subscription) {
            return [
                'current_plan' => 'Gratuito',
                'status' => 'inactive',
                'expires_at' => null,
                'features' => ['Destinos básicos', 'Promociones limitadas'],
            ];
        }

        $planFeatures = [
            'basic' => ['Destinos básicos', 'Promociones limitadas', 'Soporte por email'],
            'premium' => ['Destinos ilimitados', 'Promociones avanzadas', 'Soporte prioritario', 'Analytics detallados'],
            'enterprise' => ['Todo de Premium', 'API personalizada', 'Soporte 24/7', 'White label'],
        ];

        return [
            'current_plan' => ucfirst($subscription->plan_type),
            'status' => $subscription->status,
            'expires_at' => $subscription->expires_at,
            'features' => $planFeatures[$subscription->plan_type] ?? $planFeatures['basic'],
        ];
    }

    /**
     * @OA\Get(
     *     path="/api/v1/provider/destinos/{id}/analytics",
     *     operationId="getDestinoAnalytics",
     *     tags={"Provider Dashboard"},
     *     summary="Analytics detallados de un destino",
     *     description="Retorna estadísticas detalladas de un destino específico del proveedor",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID del destino",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Período de análisis",
     *         required=false,
     *         @OA\Schema(type="string", enum={"7d", "30d", "90d", "1y"}, default="30d")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Analytics obtenidos exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="destino", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="nombre", type="string", example="Balneario El Tephé"),
     *                     @OA\Property(property="slug", type="string", example="balneario-el-tephe"),
     *                     @OA\Property(property="status", type="string", example="published")
     *                 ),
     *                 @OA\Property(property="overview", type="object",
     *                     @OA\Property(property="total_visitas", type="integer", example=1250),
     *                     @OA\Property(property="total_favoritos", type="integer", example=89),
     *                     @OA\Property(property="total_reviews", type="integer", example=23),
     *                     @OA\Property(property="rating_promedio", type="number", format="float", example=4.3),
     *                     @OA\Property(property="total_promociones", type="integer", example=3),
     *                     @OA\Property(property="promociones_activas", type="integer", example=2)
     *                 ),
     *                 @OA\Property(property="engagement", type="object",
     *                     @OA\Property(property="favoritos_por_mes", type="array", @OA\Items(
     *                         @OA\Property(property="month", type="string", example="2025-01"),
     *                         @OA\Property(property="count", type="integer", example=15)
     *                     )),
     *                     @OA\Property(property="reviews_por_mes", type="array", @OA\Items(
     *                         @OA\Property(property="month", type="string", example="2025-01"),
     *                         @OA\Property(property="count", type="integer", example=8)
     *                     )),
     *                     @OA\Property(property="visitas_por_dia", type="array", @OA\Items(
     *                         @OA\Property(property="date", type="string", example="2025-01-15"),
     *                         @OA\Property(property="count", type="integer", example=45)
     *                     ))
     *                 ),
     *                 @OA\Property(property="reviews_analysis", type="object",
     *                     @OA\Property(property="rating_distribution", type="array", @OA\Items(
     *                         @OA\Property(property="rating", type="integer", example=5),
     *                         @OA\Property(property="count", type="integer", example=12),
     *                         @OA\Property(property="percentage", type="number", format="float", example=52.2)
     *                     )),
     *                     @OA\Property(property="recent_reviews", type="array", @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="rating", type="integer", example=5),
     *                         @OA\Property(property="comment", type="string", example="Excelente lugar"),
     *                         @OA\Property(property="user_name", type="string", example="Juan Pérez"),
     *                         @OA\Property(property="created_at", type="string", format="date-time")
     *                     ))
     *                 ),
     *                 @OA\Property(property="performance_metrics", type="object",
     *                     @OA\Property(property="conversion_rate", type="number", format="float", example=7.1),
     *                     @OA\Property(property="engagement_rate", type="number", format="float", example=12.5),
     *                     @OA\Property(property="bounce_rate", type="number", format="float", example=23.4)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Analytics del destino obtenidos exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Destino no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Destino no encontrado.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Acceso denegado. Solo para proveedores.")
     *         )
     *     )
     * )
     */
    public function getDestinoAnalytics(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isProvider()) {
                return $this->sendError('Acceso denegado. Solo para proveedores.', [], 403);
            }

            // Verificar que el destino pertenece al proveedor
            $destino = Destino::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$destino) {
                return $this->sendError('Destino no encontrado.', [], 404);
            }

            $period = $request->get('period', '30d');

            return $this->getCachedData("destino_analytics_{$id}_{$period}", function () use ($destino, $period) {
                return $this->generateDestinoAnalytics($destino, $period);
            }, 300); // Cache por 5 minutos

        } catch (\Exception $e) {
            Log::error('Error getting destino analytics: ' . $e->getMessage());
            return $this->sendError('Error al obtener analytics del destino: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/provider/promociones/{id}/analytics",
     *     operationId="getPromocionAnalytics",
     *     tags={"Provider Dashboard"},
     *     summary="Analytics detallados de una promoción",
     *     description="Retorna estadísticas detalladas de una promoción específica del proveedor",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la promoción",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         description="Período de análisis",
     *         required=false,
     *         @OA\Schema(type="string", enum={"7d", "30d", "90d", "1y"}, default="30d")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Analytics obtenidos exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="promocion", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="titulo", type="string", example="50% de descuento"),
     *                     @OA\Property(property="destino_nombre", type="string", example="Balneario El Tephé"),
     *                     @OA\Property(property="fecha_inicio", type="string", format="date", example="2025-01-01"),
     *                     @OA\Property(property="fecha_fin", type="string", format="date", example="2025-01-31"),
     *                     @OA\Property(property="status", type="string", example="active")
     *                 ),
     *                 @OA\Property(property="overview", type="object",
     *                     @OA\Property(property="total_views", type="integer", example=450),
     *                     @OA\Property(property="total_clicks", type="integer", example=67),
     *                     @OA\Property(property="conversion_rate", type="number", format="float", example=14.9),
     *                     @OA\Property(property="days_active", type="integer", example=15),
     *                     @OA\Property(property="days_remaining", type="integer", example=16)
     *                 ),
     *                 @OA\Property(property="performance", type="object",
     *                     @OA\Property(property="views_por_dia", type="array", @OA\Items(
     *                         @OA\Property(property="date", type="string", example="2025-01-15"),
     *                         @OA\Property(property="views", type="integer", example=30),
     *                         @OA\Property(property="clicks", type="integer", example=5)
     *                     )),
     *                     @OA\Property(property="hourly_performance", type="array", @OA\Items(
     *                         @OA\Property(property="hour", type="integer", example=14),
     *                         @OA\Property(property="views", type="integer", example=45),
     *                         @OA\Property(property="clicks", type="integer", example=8)
     *                     ))
     *                 ),
     *                 @OA\Property(property="audience", type="object",
     *                     @OA\Property(property="device_types", type="array", @OA\Items(
     *                         @OA\Property(property="device", type="string", example="mobile"),
     *                         @OA\Property(property="percentage", type="number", format="float", example=65.2)
     *                     )),
     *                     @OA\Property(property="locations", type="array", @OA\Items(
     *                         @OA\Property(property="location", type="string", example="Pachuca"),
     *                         @OA\Property(property="views", type="integer", example=120)
     *                     ))
     *                 ),
     *                 @OA\Property(property="comparison", type="object",
     *                     @OA\Property(property="vs_previous_period", type="object",
     *                         @OA\Property(property="views_change", type="number", format="float", example=12.5),
     *                         @OA\Property(property="clicks_change", type="number", format="float", example=8.3),
     *                         @OA\Property(property="conversion_change", type="number", format="float", example=-2.1)
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Analytics de la promoción obtenidos exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Promoción no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Promoción no encontrada.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Acceso denegado. Solo para proveedores.")
     *         )
     *     )
     * )
     */
    public function getPromocionAnalytics(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isProvider()) {
                return $this->sendError('Acceso denegado. Solo para proveedores.', [], 403);
            }

            // Verificar que la promoción pertenece al proveedor
            $promocion = Promocion::whereHas('destino', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('id', $id)
            ->with('destino')
            ->first();

            if (!$promocion) {
                return $this->sendError('Promoción no encontrada.', [], 404);
            }

            $period = $request->get('period', '30d');

            return $this->getCachedData("promocion_analytics_{$id}_{$period}", function () use ($promocion, $period) {
                return $this->generatePromocionAnalytics($promocion, $period);
            }, 300); // Cache por 5 minutos

        } catch (\Exception $e) {
            Log::error('Error getting promocion analytics: ' . $e->getMessage());
            return $this->sendError('Error al obtener analytics de la promoción: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Generar analytics de destino
     */
    private function generateDestinoAnalytics(Destino $destino, string $period): JsonResponse
    {
        // Datos del destino
        $destinoData = [
            'id' => $destino->id,
            'nombre' => $destino->nombre,
            'slug' => $destino->slug,
            'status' => $destino->status
        ];

        // Estadísticas generales
        $overview = [
            'total_visitas' => rand(800, 2000),
            'total_favoritos' => DB::table('favoritos')->where('destino_id', $destino->id)->count(),
            'total_reviews' => Review::where('destino_id', $destino->id)->count(),
            'rating_promedio' => Review::where('destino_id', $destino->id)->avg('rating') ?? 0,
            'total_promociones' => Promocion::where('destino_id', $destino->id)->count(),
            'promociones_activas' => Promocion::where('destino_id', $destino->id)
                ->where('fecha_fin', '>=', now())
                ->count()
        ];

        // Datos de engagement (simulados)
        $engagement = [
            'favoritos_por_mes' => $this->generateMonthlyData('favoritos', $period),
            'reviews_por_mes' => $this->generateMonthlyData('reviews', $period),
            'visitas_por_dia' => $this->generateDailyData('visitas', $period)
        ];

        // Análisis de reseñas
        $reviewsAnalysis = [
            'rating_distribution' => $this->generateRatingDistribution($destino->id),
            'recent_reviews' => Review::where('destino_id', $destino->id)
                ->with('user:id,name')
                ->latest()
                ->limit(5)
                ->get()
                ->map(function ($review) {
                    return [
                        'id' => $review->id,
                        'rating' => $review->rating,
                        'comment' => $review->comment,
                        'user_name' => $review->user->name,
                        'created_at' => $review->created_at
                    ];
                })
        ];

        // Métricas de rendimiento
        $performanceMetrics = [
            'conversion_rate' => rand(50, 150) / 10, // 5.0% - 15.0%
            'engagement_rate' => rand(100, 200) / 10, // 10.0% - 20.0%
            'bounce_rate' => rand(150, 350) / 10 // 15.0% - 35.0%
        ];

        $data = [
            'destino' => $destinoData,
            'overview' => $overview,
            'engagement' => $engagement,
            'reviews_analysis' => $reviewsAnalysis,
            'performance_metrics' => $performanceMetrics
        ];

        return $this->sendResponse($data, 'Analytics del destino obtenidos exitosamente.');
    }

    /**
     * Generar analytics de promoción
     */
    private function generatePromocionAnalytics(Promocion $promocion, string $period): JsonResponse
    {
        // Datos de la promoción
        $promocionData = [
            'id' => $promocion->id,
            'titulo' => $promocion->titulo,
            'destino_nombre' => $promocion->destino->nombre,
            'fecha_inicio' => $promocion->fecha_inicio,
            'fecha_fin' => $promocion->fecha_fin,
            'status' => $promocion->fecha_fin >= now() ? 'active' : 'expired'
        ];

        // Estadísticas generales
        $totalViews = rand(300, 800);
        $totalClicks = rand(30, 150);
        $conversionRate = $totalViews > 0 ? ($totalClicks / $totalViews) * 100 : 0;
        
        $overview = [
            'total_views' => $totalViews,
            'total_clicks' => $totalClicks,
            'conversion_rate' => round($conversionRate, 1),
            'days_active' => now()->diffInDays($promocion->fecha_inicio),
            'days_remaining' => max(0, now()->diffInDays($promocion->fecha_fin, false))
        ];

        // Rendimiento por día
        $performance = [
            'views_por_dia' => $this->generateDailyPromocionData($period),
            'hourly_performance' => $this->generateHourlyData()
        ];

        // Análisis de audiencia
        $audience = [
            'device_types' => [
                ['device' => 'mobile', 'percentage' => rand(600, 750) / 10],
                ['device' => 'desktop', 'percentage' => rand(200, 350) / 10],
                ['device' => 'tablet', 'percentage' => rand(50, 150) / 10]
            ],
            'locations' => [
                ['location' => 'Pachuca', 'views' => rand(80, 150)],
                ['location' => 'Tula', 'views' => rand(60, 120)],
                ['location' => 'Ixmiquilpan', 'views' => rand(40, 100)],
                ['location' => 'Otros', 'views' => rand(20, 80)]
            ]
        ];

        // Comparación con período anterior
        $comparison = [
            'vs_previous_period' => [
                'views_change' => rand(-200, 200) / 10,
                'clicks_change' => rand(-150, 150) / 10,
                'conversion_change' => rand(-50, 50) / 10
            ]
        ];

        $data = [
            'promocion' => $promocionData,
            'overview' => $overview,
            'performance' => $performance,
            'audience' => $audience,
            'comparison' => $comparison
        ];

        return $this->sendResponse($data, 'Analytics de la promoción obtenidos exitosamente.');
    }

    /**
     * Generar datos mensuales simulados
     */
    private function generateMonthlyData(string $type, string $period): array
    {
        $months = [];
        $count = match($period) {
            '7d' => 1,
            '30d' => 1,
            '90d' => 3,
            '1y' => 12,
            default => 1
        };

        for ($i = 0; $i < $count; $i++) {
            $date = now()->subMonths($i);
            $months[] = [
                'month' => $date->format('Y-m'),
                'count' => match($type) {
                    'favoritos' => rand(5, 25),
                    'reviews' => rand(2, 12),
                    default => rand(10, 50)
                }
            ];
        }

        return array_reverse($months);
    }

    /**
     * Generar datos diarios simulados
     */
    private function generateDailyData(string $type, string $period): array
    {
        $days = [];
        $count = match($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            '1y' => 365,
            default => 30
        };

        for ($i = 0; $i < $count; $i++) {
            $date = now()->subDays($i);
            $days[] = [
                'date' => $date->format('Y-m-d'),
                'count' => match($type) {
                    'visitas' => rand(20, 80),
                    default => rand(5, 25)
                }
            ];
        }

        return array_reverse($days);
    }

    /**
     * Generar datos diarios de promoción
     */
    private function generateDailyPromocionData(string $period): array
    {
        $days = [];
        $count = match($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            '1y' => 365,
            default => 30
        };

        for ($i = 0; $i < $count; $i++) {
            $date = now()->subDays($i);
            $views = rand(15, 45);
            $days[] = [
                'date' => $date->format('Y-m-d'),
                'views' => $views,
                'clicks' => rand(2, max(3, $views / 10))
            ];
        }

        return array_reverse($days);
    }

    /**
     * Generar datos por hora
     */
    private function generateHourlyData(): array
    {
        $hours = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $hours[] = [
                'hour' => $hour,
                'views' => rand(10, 50),
                'clicks' => rand(1, 8)
            ];
        }
        return $hours;
    }

    /**
     * Generar distribución de calificaciones
     */
    private function generateRatingDistribution(int $destinoId): array
    {
        $reviews = Review::where('destino_id', $destinoId)->get();
        $total = $reviews->count();
        
        if ($total === 0) {
            return [];
        }

        $distribution = [];
        for ($rating = 1; $rating <= 5; $rating++) {
            $count = $reviews->where('rating', $rating)->count();
            $distribution[] = [
                'rating' => $rating,
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0
            ];
        }

        return $distribution;
    }
} 