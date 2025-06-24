<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
            Subscription::CYCLE_MONTHLY => $start->addMonth(),
            Subscription::CYCLE_QUARTERLY => $start->addMonths(3),
            Subscription::CYCLE_YEARLY => $start->addYear(),
            default => $start->addMonth(),
        };
    }
} 