<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(
 *     name="Payments",
 *     description="API Endpoints para gestión de pagos y facturación"
 * )
 */
class PaymentController extends BaseController
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/payments/create-checkout-session",
     *     operationId="createCheckoutSession",
     *     tags={"Payments"},
     *     summary="Crear sesión de checkout para suscripción",
     *     description="Crea una sesión de checkout de Stripe para procesar el pago de una suscripción",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"plan_type"},
     *             @OA\Property(property="plan_type", type="string", enum={"basic","premium","enterprise"}, example="premium", description="Tipo de plan"),
     *             @OA\Property(property="billing_cycle", type="string", enum={"monthly","quarterly","yearly"}, example="monthly", description="Ciclo de facturación"),
     *             @OA\Property(property="success_url", type="string", example="https://app.com/success", description="URL de éxito personalizada"),
     *             @OA\Property(property="cancel_url", type="string", example="https://app.com/cancel", description="URL de cancelación personalizada")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sesión de checkout creada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Sesión de checkout creada exitosamente"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="session_id", type="string", example="cs_test_..."),
     *                 @OA\Property(property="checkout_url", type="string", example="https://checkout.stripe.com/..."),
     *                 @OA\Property(property="invoice_id", type="integer", example=1),
     *                 @OA\Property(property="amount", type="number", format="float", example=599.00),
     *                 @OA\Property(property="currency", type="string", example="mxn")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Los datos proporcionados no son válidos"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al crear sesión de checkout")
     *         )
     *     )
     * )
     */
    public function createCheckoutSession(Request $request): JsonResponse
    {
        try {
            // Validar datos de entrada
            $validated = $request->validate([
                'plan_type' => ['required', Rule::in(['basic', 'premium', 'enterprise'])],
                'billing_cycle' => ['nullable', Rule::in(['monthly', 'quarterly', 'yearly'])],
                'success_url' => 'nullable|url',
                'cancel_url' => 'nullable|url',
            ]);

            // Verificar que el usuario sea proveedor
            $user = $request->user();
            if (!$user->isProvider()) {
                return $this->errorResponse('Solo los proveedores pueden crear suscripciones', 403);
            }

            // Verificar que no tenga una suscripción activa
            if ($user->hasActiveSubscription()) {
                return $this->errorResponse('Ya tienes una suscripción activa', 422);
            }

            // Crear sesión de checkout
            $result = $this->stripeService->createCheckoutSession($validated);

            Log::info('Checkout session created', [
                'user_id' => $user->id,
                'session_id' => $result['session_id'],
                'plan_type' => $validated['plan_type'],
            ]);

            return $this->successResponse($result, 'Sesión de checkout creada exitosamente');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Los datos proporcionados no son válidos');
        } catch (\Exception $e) {
            Log::error('Error creating checkout session: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'request_data' => $request->all(),
            ]);

            return $this->errorResponse('Error al crear sesión de checkout: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/payments/webhook",
     *     operationId="handleWebhook",
     *     tags={"Payments"},
     *     summary="Manejar webhook de Stripe",
     *     description="Procesa los webhooks enviados por Stripe para actualizar el estado de pagos y suscripciones",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="payload", type="string", description="Payload del webhook"),
     *             @OA\Property(property="signature", type="string", description="Firma del webhook")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Webhook procesado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Webhook procesado correctamente"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="status", type="string", example="success"),
     *                 @OA\Property(property="event_type", type="string", example="invoice.payment_succeeded")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error en el webhook",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al procesar webhook")
     *         )
     *     )
     * )
     */
    public function webhook(Request $request): JsonResponse
    {
        try {
            // Obtener payload y firma del webhook
            $payload = $request->all();
            $signature = $request->header('Stripe-Signature');

            if (!$signature) {
                return $this->errorResponse('Firma de webhook no proporcionada', 400);
            }

            // Procesar webhook
            $result = $this->stripeService->handleWebhook(json_encode($payload), $signature);

            Log::info('Webhook processed', [
                'result' => $result,
                'signature' => $signature,
            ]);

            return $this->successResponse($result, 'Webhook procesado correctamente');

        } catch (\Exception $e) {
            Log::error('Webhook processing error: ' . $e->getMessage(), [
                'payload' => $request->all(),
                'signature' => $request->header('Stripe-Signature'),
            ]);

            return $this->errorResponse('Error al procesar webhook: ' . $e->getMessage(), 400);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/payments/invoices",
     *     operationId="getInvoices",
     *     tags={"Payments"},
     *     summary="Obtener facturas del usuario",
     *     description="Retorna las facturas del usuario autenticado con filtros y paginación",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtrar por estado de factura",
     *         required=false,
     *         @OA\Schema(type="string", enum={"draft","open","paid","void","uncollectible"})
     *     ),
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
     *     @OA\Response(
     *         response=200,
     *         description="Facturas obtenidas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Facturas obtenidas exitosamente"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="invoices", type="array", @OA\Items(ref="#/components/schemas/Invoice")),
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="per_page", type="integer", example=15),
     *                     @OA\Property(property="total", type="integer", example=25),
     *                     @OA\Property(property="last_page", type="integer", example=2)
     *                 ),
     *                 @OA\Property(property="stats", type="object",
     *                     @OA\Property(property="total_invoices", type="integer", example=25),
     *                     @OA\Property(property="paid_invoices", type="integer", example=20),
     *                     @OA\Property(property="unpaid_invoices", type="integer", example=3),
     *                     @OA\Property(property="overdue_invoices", type="integer", example=2)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado"
     *     )
     * )
     */
    public function getInvoices(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            Log::info('Getting invoices for user', ['user_id' => $user->id]);

            // Validar parámetros
            $request->validate([
                'status' => 'nullable|string|in:draft,open,paid,void,uncollectible',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $perPage = min($request->input('per_page', 15), 100);
            Log::info('Pagination settings', ['per_page' => $perPage]);
            
            // Construir consulta
            $query = Invoice::where('user_id', $user->id);

            // Aplicar filtros
            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
                Log::info('Applied status filter', ['status' => $request->input('status')]);
            }

            // Obtener facturas paginadas
            $invoices = $query->orderBy('created_at', 'desc')
                ->paginate($perPage);

            Log::info('Invoices retrieved', ['count' => $invoices->count()]);

            // Calcular estadísticas simplificadas
            $stats = [
                'total_invoices' => Invoice::where('user_id', $user->id)->count(),
                'paid_invoices' => Invoice::where('user_id', $user->id)->where('status', 'paid')->count(),
                'unpaid_invoices' => Invoice::where('user_id', $user->id)->whereIn('status', ['open', 'draft'])->count(),
                'overdue_invoices' => Invoice::where('user_id', $user->id)->where('due_date', '<', now())->whereIn('status', ['open', 'draft'])->count(),
            ];

            Log::info('Stats calculated', $stats);

            $responseData = [
                'invoices' => $invoices->items(),
                'pagination' => [
                    'current_page' => $invoices->currentPage(),
                    'per_page' => $invoices->perPage(),
                    'total' => $invoices->total(),
                    'last_page' => $invoices->lastPage(),
                ],
                'stats' => $stats,
            ];

            return $this->successResponse($responseData, 'Facturas obtenidas exitosamente');

        } catch (ValidationException $e) {
            Log::error('Validation error in getInvoices', ['errors' => $e->errors()]);
            return $this->validationErrorResponse($e->errors(), 'Los datos proporcionados no son válidos');
        } catch (\Exception $e) {
            Log::error('Error getting invoices: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('Error al obtener facturas: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/payments/update-payment-method",
     *     operationId="updatePaymentMethod",
     *     tags={"Payments"},
     *     summary="Actualizar método de pago",
     *     description="Actualiza el método de pago por defecto del usuario",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"payment_method_id"},
     *             @OA\Property(property="payment_method_id", type="string", example="pm_...", description="ID del método de pago de Stripe")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Método de pago actualizado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Método de pago actualizado exitosamente"),
     *             @OA\Property(property="data", ref="#/components/schemas/PaymentMethod")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Los datos proporcionados no son válidos"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error al actualizar método de pago")
     *         )
     *     )
     * )
     */
    public function updatePaymentMethod(Request $request): JsonResponse
    {
        try {
            // Validar datos de entrada
            $validated = $request->validate([
                'payment_method_id' => 'required|string|starts_with:pm_',
            ]);

            $user = $request->user();

            // Verificar que el usuario tenga un ID de cliente de Stripe
            if (!$user->stripe_customer_id) {
                return $this->errorResponse('Usuario no tiene cuenta de cliente configurada', 422);
            }

            // Actualizar método de pago
            $paymentMethod = $this->stripeService->updatePaymentMethod($user, $validated['payment_method_id']);

            Log::info('Payment method updated', [
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethod->id,
                'stripe_payment_method_id' => $validated['payment_method_id'],
            ]);

            return $this->successResponse($paymentMethod, 'Método de pago actualizado exitosamente');

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Los datos proporcionados no son válidos');
        } catch (\Exception $e) {
            Log::error('Error updating payment method: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'payment_method_id' => $request->input('payment_method_id'),
            ]);

            return $this->errorResponse('Error al actualizar método de pago: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/payments/payment-methods",
     *     operationId="getPaymentMethods",
     *     tags={"Payments"},
     *     summary="Obtener métodos de pago del usuario",
     *     description="Retorna los métodos de pago del usuario autenticado",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Métodos de pago obtenidos exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Métodos de pago obtenidos exitosamente"),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/PaymentMethod"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado"
     *     )
     * )
     */
    public function getPaymentMethods(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $paymentMethods = PaymentMethod::where('user_id', $user->id)
                ->orderBy('is_default', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->successResponse($paymentMethods, 'Métodos de pago obtenidos exitosamente');

        } catch (\Exception $e) {
            Log::error('Error getting payment methods: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
            ]);

            return $this->errorResponse('Error al obtener métodos de pago: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/payments/payment-methods/{paymentMethod}",
     *     operationId="deletePaymentMethod",
     *     tags={"Payments"},
     *     summary="Eliminar método de pago",
     *     description="Elimina un método de pago específico del usuario",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="paymentMethod",
     *         in="path",
     *         required=true,
     *         description="ID del método de pago",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Método de pago eliminado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Método de pago eliminado exitosamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado para eliminar este método de pago"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Método de pago no encontrado"
     *     )
     * )
     */
    public function deletePaymentMethod(Request $request, PaymentMethod $paymentMethod): JsonResponse
    {
        try {
            $user = $request->user();

            // Verificar que el método de pago pertenece al usuario
            if ($paymentMethod->user_id !== $user->id) {
                return $this->errorResponse('No autorizado para eliminar este método de pago', 403);
            }

            // Verificar que no sea el único método de pago
            $totalPaymentMethods = PaymentMethod::where('user_id', $user->id)->count();
            if ($totalPaymentMethods <= 1) {
                return $this->errorResponse('No se puede eliminar el único método de pago', 422);
            }

            // Eliminar método de pago
            $paymentMethod->delete();

            Log::info('Payment method deleted', [
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethod->id,
            ]);

            return $this->successResponse(null, 'Método de pago eliminado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error deleting payment method: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'payment_method_id' => $paymentMethod->id ?? null,
            ]);

            return $this->errorResponse('Error al eliminar método de pago: ' . $e->getMessage(), 500);
        }
    }
} 