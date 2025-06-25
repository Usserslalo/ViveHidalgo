<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use App\Notifications\PaymentSuccessful;
use App\Notifications\PaymentFailed;
use App\Notifications\SubscriptionRenewalReminder;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    /** @test */
    public function webhook_handles_invoice_payment_succeeded()
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => Subscription::STATUS_PENDING,
        ]);
        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'stripe_invoice_id' => 'in_test_123',
            'status' => Invoice::STATUS_OPEN,
        ]);

        $payload = [
            'id' => 'evt_test_123',
            'type' => 'invoice.payment_succeeded',
            'data' => [
                'object' => [
                    'id' => 'in_test_123',
                    'amount_paid' => 9999, // $99.99 in cents
                    'customer' => 'cus_test_123',
                    'subscription' => 'sub_test_123',
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/payments/webhook', $payload, [
            'Stripe-Signature' => $this->generateStripeSignature($payload)
        ]);

        $response->assertStatus(200);

        // Verificar que la factura se actualizó
        $invoice->refresh();
        $this->assertEquals(Invoice::STATUS_PAID, $invoice->status);
        $this->assertNotNull($invoice->paid_at);
        $this->assertEquals(99.99, $invoice->amount);

        // Verificar que la suscripción se activó
        $subscription->refresh();
        $this->assertEquals(Subscription::STATUS_ACTIVE, $subscription->status);

        // Verificar que se envió la notificación
        Notification::assertSentTo($user, PaymentSuccessful::class);
    }

    /** @test */
    public function webhook_handles_invoice_payment_failed()
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);
        $invoice = Invoice::factory()->create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'stripe_invoice_id' => 'in_test_456',
            'status' => Invoice::STATUS_OPEN,
        ]);

        $payload = [
            'id' => 'evt_test_456',
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'in_test_456',
                    'amount_due' => 9999,
                    'customer' => 'cus_test_123',
                    'subscription' => 'sub_test_123',
                    'attempt_count' => 3,
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/payments/webhook', $payload, [
            'Stripe-Signature' => $this->generateStripeSignature($payload)
        ]);

        $response->assertStatus(200);

        // Verificar que se envió la notificación de pago fallido
        Notification::assertSentTo($user, PaymentFailed::class);
    }

    /** @test */
    public function webhook_handles_subscription_created()
    {
        $user = User::factory()->create(['stripe_customer_id' => 'cus_test_123']);

        $payload = [
            'id' => 'evt_test_101',
            'type' => 'customer.subscription.created',
            'data' => [
                'object' => [
                    'id' => 'sub_test_123',
                    'customer' => 'cus_test_123',
                    'status' => 'active',
                    'current_period_start' => time(),
                    'current_period_end' => time() + (30 * 24 * 60 * 60),
                    'metadata' => [
                        'user_id' => $user->id,
                        'plan_type' => 'premium',
                        'billing_cycle' => 'monthly',
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/payments/webhook', $payload, [
            'Stripe-Signature' => $this->generateStripeSignature($payload)
        ]);

        $response->assertStatus(200);

        // Verificar que se creó la suscripción local
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'plan_type' => 'premium',
            'status' => Subscription::STATUS_ACTIVE,
        ]);
    }

    /** @test */
    public function webhook_handles_subscription_updated()
    {
        $user = User::factory()->create(['stripe_customer_id' => 'cus_test_123']);
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => Subscription::STATUS_ACTIVE,
            'features' => ['destinos_limit' => 5],
        ]);

        $payload = [
            'id' => 'evt_test_102',
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => [
                    'id' => 'sub_test_123',
                    'customer' => 'cus_test_123',
                    'status' => 'active',
                    'current_period_start' => time(),
                    'current_period_end' => time() + (30 * 24 * 60 * 60),
                    'metadata' => [
                        'user_id' => $user->id,
                        'plan_type' => 'premium',
                        'billing_cycle' => 'monthly',
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/payments/webhook', $payload, [
            'Stripe-Signature' => $this->generateStripeSignature($payload)
        ]);

        $response->assertStatus(200);

        // Verificar que la suscripción se actualizó
        $subscription->refresh();
        $this->assertEquals('premium', $subscription->plan_type);
    }

    /** @test */
    public function webhook_handles_subscription_deleted()
    {
        $user = User::factory()->create(['stripe_customer_id' => 'cus_test_123']);
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => Subscription::STATUS_ACTIVE,
            'features' => ['destinos_limit' => 5],
        ]);

        $payload = [
            'id' => 'evt_test_202',
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'id' => 'sub_test_123',
                    'customer' => 'cus_test_123',
                    'status' => 'canceled',
                    'canceled_at' => time(),
                    'metadata' => [
                        'user_id' => $user->id,
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/payments/webhook', $payload, [
            'Stripe-Signature' => $this->generateStripeSignature($payload)
        ]);

        $response->assertStatus(200);

        // Verificar que la suscripción se canceló
        $subscription->refresh();
        $this->assertEquals(Subscription::STATUS_CANCELLED, $subscription->status);
    }

    /** @test */
    public function webhook_handles_payment_method_attached()
    {
        $user = User::factory()->create(['stripe_customer_id' => 'cus_test_123']);

        $payload = [
            'id' => 'evt_test_303',
            'type' => 'payment_method.attached',
            'data' => [
                'object' => [
                    'id' => 'pm_test_123',
                    'customer' => 'cus_test_123',
                    'type' => 'card',
                    'card' => [
                        'last4' => '4242',
                        'brand' => 'visa',
                        'fingerprint' => 'test_fingerprint',
                        'country' => 'MX',
                        'exp_month' => 12,
                        'exp_year' => 2025,
                    ]
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/payments/webhook', $payload, [
            'Stripe-Signature' => $this->generateStripeSignature($payload)
        ]);

        $response->assertStatus(200);

        // Verificar que se creó el método de pago local
        $this->assertDatabaseHas('payment_methods', [
            'user_id' => $user->id,
            'stripe_payment_method_id' => 'pm_test_123',
            'type' => 'card',
            'last4' => '4242',
            'brand' => 'visa',
        ]);
    }

    /** @test */
    public function webhook_handles_payment_method_detached()
    {
        $user = User::factory()->create(['stripe_customer_id' => 'cus_test_123']);
        $paymentMethod = PaymentMethod::factory()->create([
            'user_id' => $user->id,
            'stripe_payment_method_id' => 'pm_test_456',
            'metadata' => ['fingerprint' => 'test'],
        ]);

        $payload = [
            'id' => 'evt_test_404',
            'type' => 'payment_method.detached',
            'data' => [
                'object' => [
                    'id' => 'pm_test_456',
                    'customer' => 'cus_test_123',
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/payments/webhook', $payload, [
            'Stripe-Signature' => $this->generateStripeSignature($payload)
        ]);

        $response->assertStatus(200);

        // Verificar que el método de pago se eliminó (soft delete)
        $this->assertSoftDeleted('payment_methods', [
            'id' => $paymentMethod->id,
        ]);
    }

    /** @test */
    public function webhook_ignores_unknown_event_types()
    {
        $payload = [
            'id' => 'evt_test_505',
            'type' => 'unknown.event.type',
            'data' => [
                'object' => []
            ]
        ];

        $response = $this->postJson('/api/v1/payments/webhook', $payload, [
            'Stripe-Signature' => $this->generateStripeSignature($payload)
        ]);

        $response->assertStatus(200);
        $response->assertJson(['data' => ['status' => 'ignored']]);
    }

    /** @test */
    public function webhook_requires_valid_signature()
    {
        $payload = [
            'id' => 'evt_test_606',
            'type' => 'invoice.payment_succeeded',
            'data' => [
                'object' => []
            ]
        ];

        $response = $this->postJson('/api/v1/payments/webhook', $payload, [
            'Stripe-Signature' => 'invalid_signature'
        ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function webhook_handles_missing_invoice_gracefully()
    {
        $payload = [
            'id' => 'evt_test_707',
            'type' => 'invoice.payment_succeeded',
            'data' => [
                'object' => [
                    'id' => 'in_nonexistent',
                    'amount_paid' => 9999,
                    'customer' => 'cus_test_123',
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/payments/webhook', $payload, [
            'Stripe-Signature' => $this->generateStripeSignature($payload)
        ]);

        $response->assertStatus(200);
        $response->assertJson(['data' => ['status' => 'warning']]);
    }

    /**
     * Generar una firma de Stripe válida para testing
     */
    private function generateStripeSignature(array $payload): string
    {
        $timestamp = time();
        $payloadString = json_encode($payload);
        $signedPayload = $timestamp . '.' . $payloadString;
        $signature = hash_hmac('sha256', $signedPayload, config('stripe.webhook_secret', 'whsec_test'));
        
        return 't=' . $timestamp . ',v1=' . $signature;
    }
} 