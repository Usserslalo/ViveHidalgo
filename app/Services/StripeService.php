<?php

namespace App\Services;

use App\Models\User;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Subscription;
use App\Notifications\PaymentSuccessful;
use App\Notifications\PaymentFailed;
use App\Notifications\SubscriptionRenewalReminder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\PaymentMethod as StripePaymentMethod;
use Stripe\Invoice as StripeInvoice;
use Stripe\Webhook;
use Stripe\Exception\ApiErrorException;
use Exception;

class StripeService
{
    /**
     * Constructor - Configurar Stripe
     */
    public function __construct()
    {
        Stripe::setApiKey(config('stripe.secret_key'));
        Stripe::setApiVersion(config('stripe.api_version'));
    }

    /**
     * Crear sesión de checkout para suscripción
     *
     * @param array $subscriptionData
     * @return array
     * @throws Exception
     */
    public function createCheckoutSession(array $subscriptionData): array
    {
        try {
            $user = auth()->user();
            
            // Validar datos de suscripción
            $this->validateSubscriptionData($subscriptionData);
            
            // Obtener configuración del plan
            $planConfig = config("stripe.plans.{$subscriptionData['plan_type']}");
            if (!$planConfig) {
                throw new Exception('Plan no válido');
            }

            // Crear o obtener cliente de Stripe
            $stripeCustomer = $this->getOrCreateStripeCustomer($user);

            // Configurar línea de precio
            $lineItems = [
                [
                    'price' => $planConfig['price_id'],
                    'quantity' => 1,
                ]
            ];

            // Configurar sesión de checkout
            $sessionData = [
                'customer' => $stripeCustomer->id,
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'subscription',
                'success_url' => config('app.frontend_url') . '/payment/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => config('app.frontend_url') . '/payment/cancel',
                'metadata' => [
                    'user_id' => $user->id,
                    'plan_type' => $subscriptionData['plan_type'],
                    'billing_cycle' => $subscriptionData['billing_cycle'] ?? 'monthly',
                ],
                'subscription_data' => [
                    'metadata' => [
                        'user_id' => $user->id,
                        'plan_type' => $subscriptionData['plan_type'],
                        'billing_cycle' => $subscriptionData['billing_cycle'] ?? 'monthly',
                    ],
                ],
            ];

            // Agregar configuración de facturación si se especifica
            if (isset($subscriptionData['billing_cycle']) && $subscriptionData['billing_cycle'] !== 'monthly') {
                $sessionData['subscription_data']['billing_cycle_anchor'] = 'now';
                $sessionData['subscription_data']['proration_behavior'] = 'create_prorations';
            }

            // Crear sesión de checkout
            $session = Session::create($sessionData);

            // Crear factura local
            $invoice = $this->createLocalInvoice($user, $subscriptionData, $session->id);

            return [
                'session_id' => $session->id,
                'checkout_url' => $session->url,
                'invoice_id' => $invoice->id,
                'amount' => $planConfig['amount'] / 100, // Convertir de centavos
                'currency' => $planConfig['currency'],
            ];

        } catch (ApiErrorException $e) {
            Log::error('Stripe API Error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'subscription_data' => $subscriptionData,
            ]);
            throw new Exception('Error al crear sesión de checkout: ' . $e->getMessage());
        } catch (Exception $e) {
            Log::error('Error creating checkout session: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'subscription_data' => $subscriptionData,
            ]);
            throw $e;
        }
    }

    /**
     * Manejar webhook de Stripe
     *
     * @param string $payload
     * @param string $signature
     * @return array
     * @throws Exception
     */
    public function handleWebhook(string $payload, string $signature): array
    {
        try {
            // Verificar firma del webhook
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                config('stripe.webhook_secret')
            );

            Log::info('Stripe webhook received', [
                'event_type' => $event->type,
                'event_id' => $event->id,
            ]);

            // Procesar evento según su tipo
            switch ($event->type) {
                case 'invoice.payment_succeeded':
                    return $this->handleInvoicePaymentSucceeded($event->data->object);
                
                case 'invoice.payment_failed':
                    return $this->handleInvoicePaymentFailed($event->data->object);
                
                case 'customer.subscription.created':
                    return $this->handleSubscriptionCreated($event->data->object);
                
                case 'customer.subscription.updated':
                    return $this->handleSubscriptionUpdated($event->data->object);
                
                case 'customer.subscription.deleted':
                    return $this->handleSubscriptionDeleted($event->data->object);
                
                case 'payment_method.attached':
                    return $this->handlePaymentMethodAttached($event->data->object);
                
                case 'payment_method.detached':
                    return $this->handlePaymentMethodDetached($event->data->object);
                
                default:
                    Log::info('Unhandled webhook event', ['event_type' => $event->type]);
                    return ['status' => 'ignored', 'message' => 'Evento no manejado'];
            }

        } catch (Exception $e) {
            Log::error('Webhook processing error: ' . $e->getMessage(), [
                'payload' => $payload,
                'signature' => $signature,
            ]);
            throw $e;
        }
    }

    /**
     * Crear cliente de Stripe
     *
     * @param User $user
     * @return Customer
     * @throws Exception
     */
    public function createCustomer(User $user): Customer
    {
        try {
            $customerData = [
                'email' => $user->email,
                'name' => $user->name,
                'metadata' => [
                    'user_id' => $user->id,
                ],
            ];

            $customer = Customer::create($customerData);

            // Actualizar usuario con ID de cliente de Stripe
            $user->update(['stripe_customer_id' => $customer->id]);

            Log::info('Stripe customer created', [
                'user_id' => $user->id,
                'stripe_customer_id' => $customer->id,
            ]);

            return $customer;

        } catch (ApiErrorException $e) {
            Log::error('Error creating Stripe customer: ' . $e->getMessage(), [
                'user_id' => $user->id,
            ]);
            throw new Exception('Error al crear cliente: ' . $e->getMessage());
        }
    }

    /**
     * Actualizar método de pago
     *
     * @param User $user
     * @param string $paymentMethodId
     * @return PaymentMethod
     * @throws Exception
     */
    public function updatePaymentMethod(User $user, string $paymentMethodId): PaymentMethod
    {
        try {
            // Obtener método de pago de Stripe
            $stripePaymentMethod = StripePaymentMethod::retrieve($paymentMethodId);

            // Verificar que el método de pago pertenece al usuario
            if ($stripePaymentMethod->customer !== $user->stripe_customer_id) {
                throw new Exception('Método de pago no válido');
            }

            // Crear o actualizar método de pago local
            $paymentMethod = PaymentMethod::updateOrCreate(
                ['stripe_payment_method_id' => $paymentMethodId],
                [
                    'user_id' => $user->id,
                    'type' => $stripePaymentMethod->type,
                    'last4' => $stripePaymentMethod->card->last4 ?? null,
                    'brand' => $stripePaymentMethod->card->brand ?? null,
                    'is_default' => true,
                    'metadata' => [
                        'fingerprint' => $stripePaymentMethod->card->fingerprint ?? null,
                        'country' => $stripePaymentMethod->card->country ?? null,
                        'exp_month' => $stripePaymentMethod->card->exp_month ?? null,
                        'exp_year' => $stripePaymentMethod->card->exp_year ?? null,
                    ],
                ]
            );

            // Establecer como método de pago por defecto
            $paymentMethod->setAsDefault();

            // Actualizar método de pago por defecto en Stripe
            Customer::update($user->stripe_customer_id, [
                'invoice_settings' => [
                    'default_payment_method' => $paymentMethodId,
                ],
            ]);

            Log::info('Payment method updated', [
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethod->id,
            ]);

            return $paymentMethod;

        } catch (ApiErrorException $e) {
            Log::error('Error updating payment method: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethodId,
            ]);
            throw new Exception('Error al actualizar método de pago: ' . $e->getMessage());
        }
    }

    /**
     * Obtener o crear cliente de Stripe
     *
     * @param User $user
     * @return Customer
     * @throws Exception
     */
    private function getOrCreateStripeCustomer(User $user): Customer
    {
        if ($user->stripe_customer_id) {
            return Customer::retrieve($user->stripe_customer_id);
        }

        return $this->createCustomer($user);
    }

    /**
     * Validar datos de suscripción
     *
     * @param array $subscriptionData
     * @throws Exception
     */
    private function validateSubscriptionData(array $subscriptionData): void
    {
        if (!isset($subscriptionData['plan_type'])) {
            throw new Exception('Tipo de plan requerido');
        }

        $validPlans = ['basic', 'premium', 'enterprise'];
        if (!in_array($subscriptionData['plan_type'], $validPlans)) {
            throw new Exception('Tipo de plan no válido');
        }

        if (isset($subscriptionData['billing_cycle'])) {
            $validCycles = ['monthly', 'quarterly', 'yearly'];
            if (!in_array($subscriptionData['billing_cycle'], $validCycles)) {
                throw new Exception('Ciclo de facturación no válido');
            }
        }
    }

    /**
     * Crear factura local
     *
     * @param User $user
     * @param array $subscriptionData
     * @param string $sessionId
     * @return Invoice
     */
    private function createLocalInvoice(User $user, array $subscriptionData, string $sessionId): Invoice
    {
        $planConfig = config("stripe.plans.{$subscriptionData['plan_type']}");
        
        return Invoice::create([
            'user_id' => $user->id,
            'amount' => $planConfig['amount'] / 100,
            'currency' => $planConfig['currency'],
            'status' => Invoice::STATUS_DRAFT,
            'due_date' => now()->addDays(30),
            'metadata' => [
                'session_id' => $sessionId,
                'plan_type' => $subscriptionData['plan_type'],
                'billing_cycle' => $subscriptionData['billing_cycle'] ?? 'monthly',
            ],
        ]);
    }

    /**
     * Manejar pago de factura exitoso
     *
     * @param object $stripeInvoice
     * @return array
     */
    private function handleInvoicePaymentSucceeded($stripeInvoice): array
    {
        return DB::transaction(function () use ($stripeInvoice) {
            // Buscar factura local
            $invoice = Invoice::where('stripe_invoice_id', $stripeInvoice->id)->first();
            
            if (!$invoice) {
                Log::warning('Local invoice not found for Stripe invoice', [
                    'stripe_invoice_id' => $stripeInvoice->id,
                ]);
                return ['status' => 'warning', 'message' => 'Factura local no encontrada'];
            }

            // Actualizar factura
            $invoice->update([
                'status' => Invoice::STATUS_PAID,
                'paid_at' => now(),
                'amount' => $stripeInvoice->amount_paid / 100,
            ]);

            // Actualizar suscripción si existe
            if ($invoice->subscription_id) {
                $subscription = $invoice->subscription;
                if ($subscription) {
                    $subscription->update([
                        'status' => Subscription::STATUS_ACTIVE,
                        'current_period_end' => now()->addDays(30),
                    ]);
                }
            }

            // Enviar notificación de pago exitoso
            $invoice->user->notify(new PaymentSuccessful($invoice));

            Log::info('Invoice payment succeeded', [
                'invoice_id' => $invoice->id,
                'stripe_invoice_id' => $stripeInvoice->id,
                'amount' => $stripeInvoice->amount_paid,
            ]);

            return ['status' => 'success', 'message' => 'Pago procesado correctamente'];
        });
    }

    /**
     * Manejar pago de factura fallido
     *
     * @param object $stripeInvoice
     * @return array
     */
    private function handleInvoicePaymentFailed($stripeInvoice): array
    {
        $invoice = Invoice::where('stripe_invoice_id', $stripeInvoice->id)->first();
        
        if ($invoice) {
            $invoice->update(['status' => Invoice::STATUS_UNCOLLECTIBLE]);
            
            // Enviar notificación de pago fallido
            $reason = $stripeInvoice->last_payment_error->message ?? 'Error en el procesamiento del pago';
            $invoice->user->notify(new PaymentFailed($invoice, $reason));
            
            Log::warning('Invoice payment failed', [
                'invoice_id' => $invoice->id,
                'stripe_invoice_id' => $stripeInvoice->id,
            ]);
        }

        return ['status' => 'warning', 'message' => 'Pago fallido procesado'];
    }

    /**
     * Manejar suscripción creada
     *
     * @param object $stripeSubscription
     * @return array
     */
    private function handleSubscriptionCreated($stripeSubscription): array
    {
        $user = User::where('stripe_customer_id', $stripeSubscription->customer)->first();
        
        if ($user) {
            // Verificar que no tenga una suscripción activa
            $existingSubscription = $user->subscription()->where('status', Subscription::STATUS_ACTIVE)->first();
            if ($existingSubscription) {
                $existingSubscription->update(['status' => Subscription::STATUS_CANCELLED]);
            }

            // Crear suscripción local
            Subscription::create([
                'user_id' => $user->id,
                'stripe_subscription_id' => $stripeSubscription->id,
                'plan_type' => $stripeSubscription->metadata->plan_type ?? 'basic',
                'status' => Subscription::STATUS_ACTIVE,
                'current_period_start' => now(),
                'current_period_end' => now()->addDays(30),
                'metadata' => [
                    'stripe_subscription' => $stripeSubscription->toArray(),
                ],
            ]);

            Log::info('Subscription created', [
                'user_id' => $user->id,
                'stripe_subscription_id' => $stripeSubscription->id,
            ]);
        }

        return ['status' => 'success', 'message' => 'Suscripción creada'];
    }

    /**
     * Manejar suscripción actualizada
     *
     * @param object $stripeSubscription
     * @return array
     */
    private function handleSubscriptionUpdated($stripeSubscription): array
    {
        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();
        
        if ($subscription) {
            $subscription->update([
                'status' => $stripeSubscription->status,
                'current_period_start' => now(),
                'current_period_end' => now()->addDays(30),
            ]);

            // Enviar recordatorio de renovación si está cerca
            if ($subscription->current_period_end->diffInDays(now()) <= 7) {
                $subscription->user->notify(new SubscriptionRenewalReminder($subscription));
            }

            Log::info('Subscription updated', [
                'subscription_id' => $subscription->id,
                'stripe_subscription_id' => $stripeSubscription->id,
            ]);
        }

        return ['status' => 'success', 'message' => 'Suscripción actualizada'];
    }

    /**
     * Manejar suscripción eliminada
     *
     * @param object $stripeSubscription
     * @return array
     */
    private function handleSubscriptionDeleted($stripeSubscription): array
    {
        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();
        
        if ($subscription) {
            $subscription->update(['status' => Subscription::STATUS_CANCELLED]);

            Log::info('Subscription cancelled', [
                'subscription_id' => $subscription->id,
                'stripe_subscription_id' => $stripeSubscription->id,
            ]);
        }

        return ['status' => 'success', 'message' => 'Suscripción cancelada'];
    }

    /**
     * Manejar método de pago adjunto
     *
     * @param object $stripePaymentMethod
     * @return array
     */
    private function handlePaymentMethodAttached($stripePaymentMethod): array
    {
        $user = User::where('stripe_customer_id', $stripePaymentMethod->customer)->first();
        
        if ($user) {
            PaymentMethod::updateOrCreate(
                ['stripe_payment_method_id' => $stripePaymentMethod->id],
                [
                    'user_id' => $user->id,
                    'type' => $stripePaymentMethod->type,
                    'last4' => $stripePaymentMethod->card->last4 ?? null,
                    'brand' => $stripePaymentMethod->card->brand ?? null,
                ]
            );

            Log::info('Payment method attached', [
                'user_id' => $user->id,
                'stripe_payment_method_id' => $stripePaymentMethod->id,
            ]);
        }

        return ['status' => 'success', 'message' => 'Método de pago adjunto'];
    }

    /**
     * Manejar método de pago desadjunto
     *
     * @param object $stripePaymentMethod
     * @return array
     */
    private function handlePaymentMethodDetached($stripePaymentMethod): array
    {
        $paymentMethod = PaymentMethod::where('stripe_payment_method_id', $stripePaymentMethod->id)->first();
        
        if ($paymentMethod) {
            $paymentMethod->delete();

            Log::info('Payment method detached', [
                'payment_method_id' => $paymentMethod->id,
                'stripe_payment_method_id' => $stripePaymentMethod->id,
            ]);
        }

        return ['status' => 'success', 'message' => 'Método de pago desadjunto'];
    }

    /**
     * Obtener cliente de Stripe
     *
     * @param string $customerId
     * @return Customer
     * @throws Exception
     */
    public function getCustomer(string $customerId): Customer
    {
        try {
            return Customer::retrieve($customerId);
        } catch (ApiErrorException $e) {
            Log::error('Error retrieving Stripe customer: ' . $e->getMessage(), [
                'customer_id' => $customerId,
            ]);
            throw new Exception('Error al obtener cliente: ' . $e->getMessage());
        }
    }
} 