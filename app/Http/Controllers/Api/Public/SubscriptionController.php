<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\BaseController;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Public Subscriptions",
 *     description="Endpoints públicos para planes de suscripción"
 * )
 */
class SubscriptionController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/v1/public/subscription/plans",
     *     operationId="getPublicSubscriptionPlans",
     *     tags={"Public Subscriptions"},
     *     summary="Obtener planes de suscripción disponibles",
     *     description="Retorna todos los planes de suscripción disponibles con precios y características",
     *     @OA\Response(
     *         response=200,
     *         description="Planes obtenidos exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="plans", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="string", example="basic"),
     *                     @OA\Property(property="name", type="string", example="Básico"),
     *                     @OA\Property(property="description", type="string", example="Plan ideal para comenzar"),
     *                     @OA\Property(property="price_monthly", type="number", format="float", example=299.00),
     *                     @OA\Property(property="price_yearly", type="number", format="float", example=2990.00),
     *                     @OA\Property(property="currency", type="string", example="MXN"),
     *                     @OA\Property(property="features", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="limits", type="object",
     *                         @OA\Property(property="destinos", type="integer", example=5),
     *                         @OA\Property(property="promociones", type="integer", example=10),
     *                         @OA\Property(property="imagenes_por_destino", type="integer", example=10)
     *                     ),
     *                     @OA\Property(property="popular", type="boolean", example=false),
     *                     @OA\Property(property="recommended", type="boolean", example=false)
     *                 )),
     *                 @OA\Property(property="current_currency", type="string", example="MXN"),
     *                 @OA\Property(property="billing_cycles", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="string", example="monthly"),
     *                     @OA\Property(property="name", type="string", example="Mensual"),
     *                     @OA\Property(property="discount", type="number", format="float", example=0)
     *                 ))
     *             ),
     *             @OA\Property(property="message", type="string", example="Planes de suscripción obtenidos exitosamente.")
     *         )
     *     )
     * )
     */
    public function getPlans(): JsonResponse
    {
        $plans = [
            [
                'id' => 'basic',
                'name' => 'Básico',
                'description' => 'Plan ideal para comenzar tu negocio turístico',
                'price_monthly' => 299.00,
                'price_yearly' => 2990.00,
                'currency' => 'MXN',
                'features' => [
                    'Hasta 5 destinos turísticos',
                    'Hasta 10 promociones activas',
                    'Soporte por email',
                    'Estadísticas básicas',
                    'Galería de imágenes (10 por destino)',
                    'Reseñas y calificaciones'
                ],
                'limits' => [
                    'destinos' => 5,
                    'promociones' => 10,
                    'imagenes_por_destino' => 10
                ],
                'popular' => false,
                'recommended' => false
            ],
            [
                'id' => 'premium',
                'name' => 'Premium',
                'description' => 'Para negocios turísticos en crecimiento',
                'price_monthly' => 599.00,
                'price_yearly' => 5990.00,
                'currency' => 'MXN',
                'features' => [
                    'Destinos ilimitados',
                    'Promociones ilimitadas',
                    'Soporte prioritario',
                    'Analytics avanzados',
                    'Galería de imágenes (50 por destino)',
                    'Reseñas y calificaciones',
                    'Integración con redes sociales',
                    'Reportes personalizados',
                    'API de acceso'
                ],
                'limits' => [
                    'destinos' => -1, // Ilimitado
                    'promociones' => -1, // Ilimitado
                    'imagenes_por_destino' => 50
                ],
                'popular' => true,
                'recommended' => true
            ],
            [
                'id' => 'enterprise',
                'name' => 'Enterprise',
                'description' => 'Solución completa para grandes operadores turísticos',
                'price_monthly' => 1299.00,
                'price_yearly' => 12990.00,
                'currency' => 'MXN',
                'features' => [
                    'Todo de Premium',
                    'Soporte 24/7',
                    'API personalizada',
                    'White label disponible',
                    'Galería de imágenes ilimitada',
                    'Integración con sistemas externos',
                    'Panel de administración personalizado',
                    'Capacitación incluida',
                    'SLA garantizado'
                ],
                'limits' => [
                    'destinos' => -1, // Ilimitado
                    'promociones' => -1, // Ilimitado
                    'imagenes_por_destino' => -1 // Ilimitado
                ],
                'popular' => false,
                'recommended' => false
            ]
        ];

        $billingCycles = [
            [
                'id' => 'monthly',
                'name' => 'Mensual',
                'discount' => 0
            ],
            [
                'id' => 'yearly',
                'name' => 'Anual',
                'discount' => 15 // 15% de descuento por pago anual
            ]
        ];

        $data = [
            'plans' => $plans,
            'current_currency' => 'MXN',
            'billing_cycles' => $billingCycles
        ];

        return $this->sendResponse($data, 'Planes de suscripción obtenidos exitosamente.');
    }
} 