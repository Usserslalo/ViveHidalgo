<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Subscriptions",
 *     description="API Endpoints para gestión de suscripciones"
 * )
 */
class SubscriptionController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/v1/subscriptions/plans",
     *     operationId="getAvailablePlans",
     *     tags={"Subscriptions"},
     *     summary="Obtener planes de suscripción disponibles",
     *     description="Retorna todos los planes de suscripción disponibles con sus características y precios",
     *     @OA\Response(
     *         response=200,
     *         description="Planes obtenidos exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Planes obtenidos exitosamente"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="plans", type="object",
     *                     @OA\Property(property="basic", type="object"),
     *                     @OA\Property(property="premium", type="object"),
     *                     @OA\Property(property="enterprise", type="object")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getAvailablePlans(): JsonResponse
    {
        try {
            $plans = Subscription::getAvailablePlans();

            return $this->sendResponse(['plans' => $plans], 'Planes obtenidos exitosamente');

        } catch (\Exception $e) {
            return $this->sendError('Error al obtener planes', $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/subscriptions/my-subscription",
     *     operationId="getMySubscription",
     *     tags={"Subscriptions"},
     *     summary="Obtener suscripción del usuario autenticado",
     *     description="Retorna la suscripción activa del usuario autenticado",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Suscripción obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Suscripción obtenida exitosamente"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="subscription", ref="#/components/schemas/Subscription"),
     *                 @OA\Property(property="plan_config", type="object"),
     *                 @OA\Property(property="subscription_stats", type="object"),
     *                 @OA\Property(property="plan_limits", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No se encontró suscripción",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No se encontró suscripción activa")
     *         )
     *     )
     * )
     */
    public function getMySubscription(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isProvider()) {
                return $this->sendError('Acceso denegado', 'Solo los proveedores pueden acceder a suscripciones', 403);
            }

            if (!$user->hasActiveSubscription()) {
                return $this->sendError('No se encontró suscripción', 'No tienes una suscripción activa', 404);
            }

            $subscription = $user->getActiveSubscription();

            $data = [
                'subscription' => $subscription,
                'plan_config' => $subscription->plan_config,
                'subscription_stats' => $user->subscription_stats,
                'plan_limits' => $user->plan_limits,
            ];

            return $this->sendResponse($data, 'Suscripción obtenida exitosamente');

        } catch (\Exception $e) {
            return $this->sendError('Error al obtener suscripción', $e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/subscriptions/subscribe",
     *     operationId="subscribe",
     *     tags={"Subscriptions"},
     *     summary="Suscribirse a un plan",
     *     description="Crea una nueva suscripción para el usuario autenticado",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"plan_type", "billing_cycle"},
     *             @OA\Property(property="plan_type", type="string", example="premium", description="Tipo de plan: basic, premium, enterprise"),
     *             @OA\Property(property="billing_cycle", type="string", example="monthly", description="Ciclo de facturación: monthly, quarterly, yearly"),
     *             @OA\Property(property="auto_renew", type="boolean", example=true, description="Renovación automática"),
     *             @OA\Property(property="payment_method", type="string", example="credit_card", description="Método de pago"),
     *             @OA\Property(property="transaction_id", type="string", example="txn_123456789", description="ID de transacción externa")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Suscripción creada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Suscripción creada exitosamente"),
     *             @OA\Property(property="data", ref="#/components/schemas/Subscription")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error de validación"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function subscribe(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isProvider()) {
                return $this->sendError('Acceso denegado', 'Solo los proveedores pueden suscribirse', 403);
            }

            // Verificar si ya tiene una suscripción activa
            if ($user->hasActiveSubscription()) {
                return $this->sendError('Suscripción existente', 'Ya tienes una suscripción activa', 422);
            }

            $validated = $request->validate([
                'plan_type' => ['required', Rule::in([Subscription::PLAN_BASIC, Subscription::PLAN_PREMIUM, Subscription::PLAN_ENTERPRISE])],
                'billing_cycle' => ['required', Rule::in([Subscription::CYCLE_MONTHLY, Subscription::CYCLE_QUARTERLY, Subscription::CYCLE_YEARLY])],
                'auto_renew' => 'boolean',
                'payment_method' => 'nullable|string|max:50',
                'transaction_id' => 'nullable|string|max:100',
            ]);

            // Calcular fechas y precio
            $amount = Subscription::calculatePrice($validated['plan_type'], $validated['billing_cycle']);
            $startDate = now();
            $endDate = $this->calculateEndDate($startDate, $validated['billing_cycle']);
            $nextBillingDate = $endDate;

            // Crear suscripción
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_type' => $validated['plan_type'],
                'status' => Subscription::STATUS_ACTIVE,
                'amount' => $amount,
                'currency' => 'MXN',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'next_billing_date' => $nextBillingDate,
                'billing_cycle' => $validated['billing_cycle'],
                'auto_renew' => $validated['auto_renew'] ?? true,
                'payment_method' => $validated['payment_method'] ?? null,
                'payment_status' => Subscription::PAYMENT_COMPLETED,
                'transaction_id' => $validated['transaction_id'] ?? null,
                'features' => Subscription::getPlanConfig($validated['plan_type'])['features'],
            ]);

            return $this->sendResponse(
                $subscription->load('user'), 
                'Suscripción creada exitosamente',
                201
            );

        } catch (ValidationException $e) {
            return $this->sendError('Error de validación', $e->errors());
        } catch (\Exception $e) {
            return $this->sendError('Error al crear suscripción', $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/subscriptions/cancel",
     *     operationId="cancelSubscription",
     *     tags={"Subscriptions"},
     *     summary="Cancelar suscripción",
     *     description="Cancela la suscripción activa del usuario",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Suscripción cancelada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Suscripción cancelada exitosamente"),
     *             @OA\Property(property="data", ref="#/components/schemas/Subscription")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No se encontró suscripción",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No se encontró suscripción activa")
     *         )
     *     )
     * )
     */
    public function cancel(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isProvider()) {
                return $this->sendError('Acceso denegado', 'Solo los proveedores pueden cancelar suscripciones', 403);
            }

            if (!$user->hasActiveSubscription()) {
                return $this->sendError('No se encontró suscripción', 'No tienes una suscripción activa', 404);
            }

            $subscription = $user->getActiveSubscription();
            $subscription->cancel();

            return $this->sendResponse(
                $subscription->load('user'), 
                'Suscripción cancelada exitosamente'
            );

        } catch (\Exception $e) {
            return $this->sendError('Error al cancelar suscripción', $e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/subscriptions/renew",
     *     operationId="renewSubscription",
     *     tags={"Subscriptions"},
     *     summary="Renovar suscripción",
     *     description="Renueva la suscripción del usuario",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="transaction_id", type="string", example="txn_123456789", description="ID de transacción externa")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Suscripción renovada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Suscripción renovada exitosamente"),
     *             @OA\Property(property="data", ref="#/components/schemas/Subscription")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No se encontró suscripción",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No se encontró suscripción")
     *         )
     *     )
     * )
     */
    public function renew(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isProvider()) {
                return $this->sendError('Acceso denegado', 'Solo los proveedores pueden renovar suscripciones', 403);
            }

            if (!$user->subscription) {
                return $this->sendError('No se encontró suscripción', 'No tienes una suscripción', 404);
            }

            $validated = $request->validate([
                'transaction_id' => 'nullable|string|max:100',
            ]);

            $subscription = $user->subscription;
            
            // Calcular nueva fecha de fin
            $newEndDate = $this->calculateEndDate($subscription->end_date, $subscription->billing_cycle);
            
            $subscription->update([
                'status' => Subscription::STATUS_ACTIVE,
                'end_date' => $newEndDate,
                'next_billing_date' => $newEndDate,
                'auto_renew' => true,
                'payment_status' => Subscription::PAYMENT_COMPLETED,
                'transaction_id' => $validated['transaction_id'] ?? null,
            ]);

            return $this->sendResponse(
                $subscription->load('user'), 
                'Suscripción renovada exitosamente'
            );

        } catch (ValidationException $e) {
            return $this->sendError('Error de validación', $e->errors());
        } catch (\Exception $e) {
            return $this->sendError('Error al renovar suscripción', $e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/subscriptions/limits",
     *     operationId="getPlanLimits",
     *     tags={"Subscriptions"},
     *     summary="Obtener límites del plan actual",
     *     description="Retorna los límites y uso actual del plan de suscripción",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Límites obtenidos exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Límites obtenidos exitosamente"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="plan_limits", type="object"),
     *                 @OA\Property(property="subscription_stats", type="object")
     *             )
     *         )
     *     )
     * )
     */
    public function getLimits(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isProvider()) {
                return $this->sendError('Acceso denegado', 'Solo los proveedores pueden acceder a límites', 403);
            }

            $data = [
                'plan_limits' => $user->plan_limits,
                'subscription_stats' => $user->subscription_stats,
            ];

            return $this->sendResponse($data, 'Límites obtenidos exitosamente');

        } catch (\Exception $e) {
            return $this->sendError('Error al obtener límites', $e->getMessage());
        }
    }

    /**
     * Calcular fecha de fin según ciclo de facturación
     */
    private function calculateEndDate($startDate, $billingCycle): \Carbon\Carbon
    {
        $start = \Carbon\Carbon::parse($startDate);

        return match ($billingCycle) {
            'monthly' => $start->addMonth(),
            'quarterly' => $start->addMonths(3),
            'yearly' => $start->addYear(),
            default => $start->addMonth(),
        };
    }

    /**
     * @OA\Post(
     *     path="/api/v1/subscription/create-checkout-session",
     *     operationId="createCheckoutSession",
     *     tags={"Subscriptions"},
     *     summary="Crear sesión de checkout para suscripción",
     *     description="Crea una sesión de checkout con Stripe/PayPal para procesar el pago de suscripción",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"plan_id", "billing_cycle"},
     *             @OA\Property(property="plan_id", type="string", example="premium", description="ID del plan seleccionado"),
     *             @OA\Property(property="billing_cycle", type="string", example="monthly", description="Ciclo de facturación"),
     *             @OA\Property(property="payment_method", type="string", example="stripe", description="Método de pago (stripe/paypal)"),
     *             @OA\Property(property="success_url", type="string", example="https://app.vivehidalgo.com/success", description="URL de éxito"),
     *             @OA\Property(property="cancel_url", type="string", example="https://app.vivehidalgo.com/cancel", description="URL de cancelación")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sesión de checkout creada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="checkout_url", type="string", example="https://checkout.stripe.com/pay/cs_test_..."),
     *                 @OA\Property(property="session_id", type="string", example="cs_test_..."),
     *                 @OA\Property(property="expires_at", type="string", format="date-time")
     *             ),
     *             @OA\Property(property="message", type="string", example="Sesión de checkout creada exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Los datos proporcionados no son válidos."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function createCheckoutSession(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isProvider()) {
                return $this->sendError('Acceso denegado. Solo para proveedores.', [], 403);
            }

            // Verificar si ya tiene una suscripción activa
            if ($user->hasActiveSubscription()) {
                return $this->sendError('Ya tienes una suscripción activa.', [], 422);
            }

            // Validar datos
            $validated = $request->validate([
                'plan_id' => 'required|in:basic,premium,enterprise',
                'billing_cycle' => 'required|in:monthly,yearly',
                'payment_method' => 'required|in:stripe,paypal',
                'success_url' => 'required|url',
                'cancel_url' => 'required|url',
            ]);

            // Obtener información del plan
            $planConfig = $this->getPlanConfig($validated['plan_id']);
            $price = $this->calculatePrice($validated['plan_id'], $validated['billing_cycle']);

            // Crear sesión de checkout (simulado por ahora)
            // En producción, aquí se integraría con Stripe o PayPal
            $sessionId = 'cs_test_' . uniqid();
            $checkoutUrl = $this->generateCheckoutUrl($validated['payment_method'], $sessionId, $price, $validated);

            // Guardar información de la sesión en cache
            $sessionData = [
                'user_id' => $user->id,
                'plan_id' => $validated['plan_id'],
                'billing_cycle' => $validated['billing_cycle'],
                'payment_method' => $validated['payment_method'],
                'amount' => $price,
                'currency' => 'MXN',
                'created_at' => now(),
                'expires_at' => now()->addHours(24),
            ];

            cache()->put("checkout_session_{$sessionId}", $sessionData, now()->addHours(24));

            return $this->sendResponse([
                'checkout_url' => $checkoutUrl,
                'session_id' => $sessionId,
                'expires_at' => $sessionData['expires_at'],
            ], 'Sesión de checkout creada exitosamente.');

        } catch (\Exception $e) {
            Log::error('Error creating checkout session: ' . $e->getMessage());
            return $this->sendError('Error al crear la sesión de checkout: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/subscription/webhook",
     *     operationId="subscriptionWebhook",
     *     tags={"Subscriptions"},
     *     summary="Webhook para procesar eventos de pago",
     *     description="Procesa webhooks de Stripe/PayPal para actualizar el estado de las suscripciones",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", example="checkout.session.completed"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="object", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Webhook procesado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Webhook procesado exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Webhook inválido",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Webhook inválido.")
     *         )
     *     )
     * )
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            $eventType = $payload['type'] ?? null;

            Log::info('Subscription webhook received', [
                'type' => $eventType,
                'payload' => $payload
            ]);

            // Verificar la autenticidad del webhook (en producción)
            // $this->verifyWebhookSignature($request);

            switch ($eventType) {
                case 'checkout.session.completed':
                    return $this->handleCheckoutCompleted($payload);
                
                case 'invoice.payment_succeeded':
                    return $this->handlePaymentSucceeded($payload);
                
                case 'invoice.payment_failed':
                    return $this->handlePaymentFailed($payload);
                
                case 'customer.subscription.deleted':
                    return $this->handleSubscriptionCancelled($payload);
                
                default:
                    Log::info('Unhandled webhook event', ['type' => $eventType]);
                    return $this->sendResponse(null, 'Evento no manejado.');
            }

        } catch (\Exception $e) {
            Log::error('Webhook processing error: ' . $e->getMessage(), [
                'payload' => $request->all()
            ]);
            return $this->sendError('Error procesando webhook: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Manejar checkout completado
     */
    private function handleCheckoutCompleted(array $payload): JsonResponse
    {
        try {
            $sessionId = $payload['data']['object']['id'] ?? null;
            
            if (!$sessionId) {
                return $this->sendError('ID de sesión no encontrado.', [], 400);
            }

            // Obtener datos de la sesión desde cache
            $sessionData = cache()->get("checkout_session_{$sessionId}");
            
            if (!$sessionData) {
                return $this->sendError('Sesión de checkout no encontrada.', [], 400);
            }

            // Crear la suscripción
            $subscription = Subscription::create([
                'user_id' => $sessionData['user_id'],
                'plan_type' => $sessionData['plan_id'],
                'status' => 'active',
                'amount' => $sessionData['amount'],
                'currency' => $sessionData['currency'],
                'start_date' => now(),
                'end_date' => $this->calculateEndDate(now(), $sessionData['billing_cycle']),
                'next_billing_date' => $this->calculateEndDate(now(), $sessionData['billing_cycle']),
                'billing_cycle' => $sessionData['billing_cycle'],
                'auto_renew' => true,
                'payment_method' => $sessionData['payment_method'],
                'payment_status' => 'completed',
                'transaction_id' => $sessionId,
                'features' => $this->getPlanConfig($sessionData['plan_id'])['features'],
            ]);

            // Limpiar cache de la sesión
            cache()->forget("checkout_session_{$sessionId}");

            Log::info('Subscription created successfully', [
                'subscription_id' => $subscription->id,
                'user_id' => $sessionData['user_id']
            ]);

            return $this->sendResponse(null, 'Suscripción creada exitosamente.');

        } catch (\Exception $e) {
            Log::error('Error handling checkout completed: ' . $e->getMessage());
            return $this->sendError('Error procesando checkout completado.', [], 500);
        }
    }

    /**
     * Manejar pago exitoso
     */
    private function handlePaymentSucceeded(array $payload): JsonResponse
    {
        // Implementar lógica para renovaciones automáticas
        Log::info('Payment succeeded', $payload);
        return $this->sendResponse(null, 'Pago procesado exitosamente.');
    }

    /**
     * Manejar pago fallido
     */
    private function handlePaymentFailed(array $payload): JsonResponse
    {
        // Implementar lógica para manejar pagos fallidos
        Log::warning('Payment failed', $payload);
        return $this->sendResponse(null, 'Pago fallido registrado.');
    }

    /**
     * Manejar suscripción cancelada
     */
    private function handleSubscriptionCancelled(array $payload): JsonResponse
    {
        // Implementar lógica para cancelar suscripciones
        Log::info('Subscription cancelled', $payload);
        return $this->sendResponse(null, 'Suscripción cancelada.');
    }

    /**
     * Obtener configuración del plan
     */
    private function getPlanConfig(string $planId): array
    {
        $configs = [
            'basic' => [
                'features' => ['Destinos básicos', 'Promociones limitadas', 'Soporte por email'],
                'limits' => ['destinos' => 5, 'promociones' => 10]
            ],
            'premium' => [
                'features' => ['Destinos ilimitados', 'Promociones avanzadas', 'Soporte prioritario', 'Analytics detallados'],
                'limits' => ['destinos' => -1, 'promociones' => -1]
            ],
            'enterprise' => [
                'features' => ['Todo de Premium', 'API personalizada', 'Soporte 24/7', 'White label'],
                'limits' => ['destinos' => -1, 'promociones' => -1]
            ]
        ];

        return $configs[$planId] ?? $configs['basic'];
    }

    /**
     * Calcular precio del plan
     */
    private function calculatePrice(string $planId, string $billingCycle): float
    {
        $prices = [
            'basic' => ['monthly' => 299.00, 'yearly' => 2990.00],
            'premium' => ['monthly' => 599.00, 'yearly' => 5990.00],
            'enterprise' => ['monthly' => 1299.00, 'yearly' => 12990.00]
        ];

        return $prices[$planId][$billingCycle] ?? 299.00;
    }

    /**
     * Generar URL de checkout (simulado)
     */
    private function generateCheckoutUrl(string $paymentMethod, string $sessionId, float $amount, array $data): string
    {
        if ($paymentMethod === 'stripe') {
            return "https://checkout.stripe.com/pay/{$sessionId}";
        } else {
            return "https://www.paypal.com/checkoutnow?token={$sessionId}";
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/subscription/invoices",
     *     operationId="getInvoices",
     *     tags={"Subscriptions"},
     *     summary="Obtener facturas del usuario",
     *     description="Retorna el historial de facturas del usuario autenticado",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número de página",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Elementos por página",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtrar por estado",
     *         required=false,
     *         @OA\Schema(type="string", enum={"paid", "pending", "failed", "refunded"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Facturas obtenidas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="invoices", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="string", example="inv_123456789"),
     *                     @OA\Property(property="number", type="string", example="INV-2025-001"),
     *                     @OA\Property(property="amount", type="number", format="float", example=299.00),
     *                     @OA\Property(property="currency", type="string", example="MXN"),
     *                     @OA\Property(property="status", type="string", example="paid"),
     *                     @OA\Property(property="due_date", type="string", format="date", example="2025-01-15"),
     *                     @OA\Property(property="paid_at", type="string", format="date-time", nullable=true),
     *                     @OA\Property(property="description", type="string", example="Suscripción Premium - Enero 2025"),
     *                     @OA\Property(property="pdf_url", type="string", nullable=true, example="https://api.stripe.com/invoices/inv_123456789/pdf")
     *                 )),
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="per_page", type="integer", example=15),
     *                     @OA\Property(property="total", type="integer", example=25)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Facturas obtenidas exitosamente.")
     *         )
     *     )
     * )
     */
    public function getInvoices(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isProvider()) {
                return $this->sendError('Acceso denegado. Solo para proveedores.', [], 403);
            }

            $perPage = $request->get('per_page', 15);
            $status = $request->get('status');

            // Simular datos de facturas (en producción vendrían de Stripe)
            $invoices = $this->generateMockInvoices($user->id, $status);

            // Paginación manual
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $perPage;
            $paginatedInvoices = array_slice($invoices, $offset, $perPage);

            $data = [
                'invoices' => $paginatedInvoices,
                'pagination' => [
                    'current_page' => (int) $page,
                    'per_page' => (int) $perPage,
                    'total' => count($invoices),
                    'last_page' => ceil(count($invoices) / $perPage),
                    'from' => $offset + 1,
                    'to' => min($offset + $perPage, count($invoices))
                ]
            ];

            return $this->sendResponse($data, 'Facturas obtenidas exitosamente.');

        } catch (\Exception $e) {
            Log::error('Error getting invoices: ' . $e->getMessage());
            return $this->sendError('Error al obtener facturas: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/subscription/invoices/{id}",
     *     operationId="getInvoice",
     *     tags={"Subscriptions"},
     *     summary="Obtener factura específica",
     *     description="Retorna los detalles de una factura específica",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la factura",
     *         required=true,
     *         @OA\Schema(type="string", example="inv_123456789")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Factura obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="invoice", type="object",
     *                     @OA\Property(property="id", type="string", example="inv_123456789"),
     *                     @OA\Property(property="number", type="string", example="INV-2025-001"),
     *                     @OA\Property(property="amount", type="number", format="float", example=299.00),
     *                     @OA\Property(property="currency", type="string", example="MXN"),
     *                     @OA\Property(property="status", type="string", example="paid"),
     *                     @OA\Property(property="due_date", type="string", format="date", example="2025-01-15"),
     *                     @OA\Property(property="paid_at", type="string", format="date-time", nullable=true),
     *                     @OA\Property(property="description", type="string", example="Suscripción Premium - Enero 2025"),
     *                     @OA\Property(property="pdf_url", type="string", nullable=true),
     *                     @OA\Property(property="items", type="array", @OA\Items(
     *                         @OA\Property(property="description", type="string", example="Suscripción Premium"),
     *                         @OA\Property(property="amount", type="number", format="float", example=299.00),
     *                         @OA\Property(property="quantity", type="integer", example=1)
     *                     )),
     *                     @OA\Property(property="customer", type="object",
     *                         @OA\Property(property="name", type="string", example="Juan Pérez"),
     *                         @OA\Property(property="email", type="string", example="juan@example.com")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Factura obtenida exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Factura no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Factura no encontrada.")
     *         )
     *     )
     * )
     */
    public function getInvoice(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isProvider()) {
                return $this->sendError('Acceso denegado. Solo para proveedores.', [], 403);
            }

            // Simular búsqueda de factura específica
            $invoice = $this->generateMockInvoice($id, $user->id);

            if (!$invoice) {
                return $this->sendError('Factura no encontrada.', [], 404);
            }

            return $this->sendResponse(['invoice' => $invoice], 'Factura obtenida exitosamente.');

        } catch (\Exception $e) {
            Log::error('Error getting invoice: ' . $e->getMessage());
            return $this->sendError('Error al obtener factura: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/subscription/update-payment-method",
     *     operationId="updatePaymentMethod",
     *     tags={"Subscriptions"},
     *     summary="Actualizar método de pago",
     *     description="Actualiza el método de pago del usuario para futuras suscripciones",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"payment_method_id"},
     *             @OA\Property(property="payment_method_id", type="string", example="pm_123456789", description="ID del método de pago de Stripe"),
     *             @OA\Property(property="set_as_default", type="boolean", example=true, description="Establecer como método predeterminado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Método de pago actualizado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="payment_method", type="object",
     *                     @OA\Property(property="id", type="string", example="pm_123456789"),
     *                     @OA\Property(property="type", type="string", example="card"),
     *                     @OA\Property(property="last4", type="string", example="4242"),
     *                     @OA\Property(property="brand", type="string", example="visa"),
     *                     @OA\Property(property="exp_month", type="integer", example=12),
     *                     @OA\Property(property="exp_year", type="integer", example=2025),
     *                     @OA\Property(property="is_default", type="boolean", example=true)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Método de pago actualizado exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Los datos proporcionados no son válidos."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function updatePaymentMethod(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user->isProvider()) {
                return $this->sendError('Acceso denegado. Solo para proveedores.', [], 403);
            }

            $validated = $request->validate([
                'payment_method_id' => 'required|string|max:100',
                'set_as_default' => 'boolean',
            ]);

            // Simular actualización de método de pago (en producción se integraría con Stripe)
            $paymentMethod = $this->simulatePaymentMethodUpdate($user->id, $validated);

            return $this->sendResponse(['payment_method' => $paymentMethod], 'Método de pago actualizado exitosamente.');

        } catch (\Exception $e) {
            Log::error('Error updating payment method: ' . $e->getMessage());
            return $this->sendError('Error al actualizar método de pago: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Generar facturas simuladas
     */
    private function generateMockInvoices(int $userId, ?string $status = null): array
    {
        $invoices = [
            [
                'id' => 'inv_' . uniqid(),
                'number' => 'INV-2025-001',
                'amount' => 299.00,
                'currency' => 'MXN',
                'status' => 'paid',
                'due_date' => '2025-01-15',
                'paid_at' => '2025-01-15T10:30:00Z',
                'description' => 'Suscripción Premium - Enero 2025',
                'pdf_url' => 'https://api.stripe.com/invoices/inv_' . uniqid() . '/pdf'
            ],
            [
                'id' => 'inv_' . uniqid(),
                'number' => 'INV-2024-012',
                'amount' => 299.00,
                'currency' => 'MXN',
                'status' => 'paid',
                'due_date' => '2024-12-15',
                'paid_at' => '2024-12-15T09:15:00Z',
                'description' => 'Suscripción Premium - Diciembre 2024',
                'pdf_url' => 'https://api.stripe.com/invoices/inv_' . uniqid() . '/pdf'
            ],
            [
                'id' => 'inv_' . uniqid(),
                'number' => 'INV-2024-011',
                'amount' => 299.00,
                'currency' => 'MXN',
                'status' => 'paid',
                'due_date' => '2024-11-15',
                'paid_at' => '2024-11-15T14:20:00Z',
                'description' => 'Suscripción Premium - Noviembre 2024',
                'pdf_url' => 'https://api.stripe.com/invoices/inv_' . uniqid() . '/pdf'
            ],
            [
                'id' => 'inv_' . uniqid(),
                'number' => 'INV-2025-002',
                'amount' => 299.00,
                'currency' => 'MXN',
                'status' => 'pending',
                'due_date' => '2025-02-15',
                'paid_at' => null,
                'description' => 'Suscripción Premium - Febrero 2025',
                'pdf_url' => null
            ]
        ];

        if ($status) {
            $invoices = array_filter($invoices, fn($invoice) => $invoice['status'] === $status);
        }

        return array_values($invoices);
    }

    /**
     * Generar factura específica simulada
     */
    private function generateMockInvoice(string $id, int $userId): ?array
    {
        // Simular búsqueda de factura específica
        $invoices = $this->generateMockInvoices($userId);
        
        foreach ($invoices as $invoice) {
            if ($invoice['id'] === $id) {
                // Agregar detalles adicionales para la vista específica
                $invoice['items'] = [
                    [
                        'description' => 'Suscripción Premium',
                        'amount' => 299.00,
                        'quantity' => 1
                    ]
                ];
                
                $invoice['customer'] = [
                    'name' => 'Juan Pérez',
                    'email' => 'juan@example.com'
                ];
                
                return $invoice;
            }
        }
        
        return null;
    }

    /**
     * Simular actualización de método de pago
     */
    private function simulatePaymentMethodUpdate(int $userId, array $data): array
    {
        return [
            'id' => $data['payment_method_id'],
            'type' => 'card',
            'last4' => '4242',
            'brand' => 'visa',
            'exp_month' => 12,
            'exp_year' => 2025,
            'is_default' => $data['set_as_default'] ?? false
        ];
    }
} 