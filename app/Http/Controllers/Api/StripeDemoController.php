<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\PaymentMethod;

/**
 * Controlador de demostración para mostrar cómo usar Stripe
 * Este controlador es solo para fines educativos
 */
class StripeDemoController extends BaseController
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Obtener la clave pública de Stripe para el frontend
     */
    public function getPublishableKey(): JsonResponse
    {
        return $this->successResponse([
            'publishable_key' => config('stripe.publishable_key'),
            'currency' => config('stripe.currency'),
        ], 'Clave pública obtenida');
    }

    /**
     * Crear una sesión de checkout simple
     */
    public function createSimpleCheckout(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'plan_type' => 'required|in:basic,premium,enterprise',
                'success_url' => 'nullable|url',
                'cancel_url' => 'nullable|url',
            ]);

            $user = $request->user();
            
            // Configurar Stripe
            Stripe::setApiKey(config('stripe.secret_key'));

            // Obtener configuración del plan
            $plans = config('stripe.plans');
            $plan = $plans[$request->plan_type];

            // Crear o obtener customer
            $customer = $this->getOrCreateCustomer($user);

            // Crear sesión de checkout
            $sessionData = [
                'customer' => $customer->id,
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => $plan['currency'],
                        'product_data' => [
                            'name' => $plan['name'],
                            'description' => "Suscripción {$plan['name']} - Vive Hidalgo",
                        ],
                        'unit_amount' => $plan['amount'],
                        'recurring' => [
                            'interval' => $plan['interval'],
                        ],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'subscription',
                'success_url' => $request->success_url ?? 'http://localhost:3000/payment/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $request->cancel_url ?? 'http://localhost:3000/payment/cancel',
                'metadata' => [
                    'user_id' => $user->id,
                    'plan_type' => $request->plan_type,
                ],
            ];

            $session = Session::create($sessionData);

            return $this->successResponse([
                'session_id' => $session->id,
                'checkout_url' => $session->url,
                'amount' => $plan['amount'] / 100,
                'currency' => $plan['currency'],
                'plan_name' => $plan['name'],
            ], 'Sesión de checkout creada');

        } catch (\Exception $e) {
            Log::error('Error creating checkout session: ' . $e->getMessage());
            return $this->errorResponse('Error al crear sesión de checkout: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Procesar pago con tarjeta (ejemplo con Payment Intents)
     */
    public function processPayment(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:100',
                'currency' => 'required|string|size:3',
                'payment_method_id' => 'required|string',
            ]);

            $user = $request->user();
            
            // Configurar Stripe
            Stripe::setApiKey(config('stripe.secret_key'));

            // Crear o obtener customer
            $customer = $this->getOrCreateCustomer($user);

            // Crear Payment Intent
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $request->amount * 100, // Stripe usa centavos
                'currency' => $request->currency,
                'customer' => $customer->id,
                'payment_method' => $request->payment_method_id,
                'confirmation_method' => 'manual',
                'confirm' => true,
                'return_url' => 'http://localhost:3000/payment/confirm',
                'metadata' => [
                    'user_id' => $user->id,
                    'description' => 'Pago de prueba - Vive Hidalgo',
                ],
            ]);

            return $this->successResponse([
                'payment_intent_id' => $paymentIntent->id,
                'client_secret' => $paymentIntent->client_secret,
                'status' => $paymentIntent->status,
                'amount' => $paymentIntent->amount / 100,
                'currency' => $paymentIntent->currency,
            ], 'Pago procesado correctamente');

        } catch (\Exception $e) {
            Log::error('Error processing payment: ' . $e->getMessage());
            return $this->errorResponse('Error al procesar pago: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener métodos de pago del usuario
     */
    public function getPaymentMethods(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user->stripe_customer_id) {
                return $this->successResponse([], 'Usuario no tiene métodos de pago');
            }

            // Configurar Stripe
            Stripe::setApiKey(config('stripe.secret_key'));

            $paymentMethods = PaymentMethod::all([
                'customer' => $user->stripe_customer_id,
                'type' => 'card',
            ]);

            $formattedMethods = [];
            foreach ($paymentMethods->data as $method) {
                $formattedMethods[] = [
                    'id' => $method->id,
                    'type' => $method->type,
                    'card' => [
                        'brand' => $method->card->brand,
                        'last4' => $method->card->last4,
                        'exp_month' => $method->card->exp_month,
                        'exp_year' => $method->card->exp_year,
                    ],
                ];
            }

            return $this->successResponse($formattedMethods, 'Métodos de pago obtenidos');

        } catch (\Exception $e) {
            Log::error('Error getting payment methods: ' . $e->getMessage());
            return $this->errorResponse('Error al obtener métodos de pago: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener o crear customer en Stripe
     */
    private function getOrCreateCustomer(User $user)
    {
        if ($user->stripe_customer_id) {
            try {
                return Customer::retrieve($user->stripe_customer_id);
            } catch (\Exception $e) {
                // Si el customer no existe, crear uno nuevo
                Log::warning("Customer {$user->stripe_customer_id} no encontrado, creando nuevo");
            }
        }

        // Crear nuevo customer
        $customer = Customer::create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => $user->id,
                'created_from' => 'api',
            ],
        ]);

        // Actualizar usuario con el customer ID
        $user->update(['stripe_customer_id' => $customer->id]);

        return $customer;
    }

    /**
     * Obtener información del customer
     */
    public function getCustomerInfo(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user->stripe_customer_id) {
                return $this->successResponse(null, 'Usuario no tiene cuenta de cliente');
            }

            // Configurar Stripe
            Stripe::setApiKey(config('stripe.secret_key'));

            $customer = Customer::retrieve($user->stripe_customer_id);

            return $this->successResponse([
                'customer_id' => $customer->id,
                'email' => $customer->email,
                'name' => $customer->name,
                'created' => $customer->created,
                'subscriptions' => $customer->subscriptions->data,
            ], 'Información del cliente obtenida');

        } catch (\Exception $e) {
            Log::error('Error getting customer info: ' . $e->getMessage());
            return $this->errorResponse('Error al obtener información del cliente: ' . $e->getMessage(), 500);
        }
    }
} 