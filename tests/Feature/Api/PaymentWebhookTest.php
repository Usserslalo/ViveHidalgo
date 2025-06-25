<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Mockery;
use Illuminate\Support\Facades\Notification;
use App\Notifications\PaymentSuccessful;
use App\Notifications\PaymentFailed;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $stripeService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'stripe_customer_id' => 'cus_test_123',
        ]);
        $this->user->assignRole('provider');
        
        // Mock StripeService
        $this->stripeService = Mockery::mock(StripeService::class);
        $this->app->instance(StripeService::class, $this->stripeService);
        
        // Disable notifications for testing
        Notification::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_handles_invoice_payment_succeeded_webhook()
    {
        // Crear factura de prueba
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'stripe_invoice_id' => 'inv_test_123',
            'status' => 'open',
            'amount' => 299.00,
        ]);

        $this->stripeService
            ->shouldReceive('handleWebhook')
            ->once()
            ->andReturn([
                'status' => 'success',
                'message' => 'Pago procesado correctamente',
            ]);

        $payload = [
            'type' => 'invoice.payment_succeeded',
            'data' => [
                'object' => [
                    'id' => 'inv_test_123',
                    'amount_paid' => 29900,
                    'customer' => 'cus_test_123',
                ],
            ],
        ];

        $response = $this->withHeaders([
            'Stripe-Signature' => 'whsec_test_signature',
        ])->postJson('/api/v1/payments/webhook', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'status',
                    'message',
                ],
            ]);

        // Verificar que la factura se actualizó
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'paid',
        ]);
    }

    /** @test */
    public function it_handles_invoice_payment_failed_webhook()
    {
        // Crear factura de prueba
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'stripe_invoice_id' => 'inv_test_456',
            'status' => 'open',
            'amount' => 299.00,
        ]);

        $this->stripeService
            ->shouldReceive('handleWebhook')
            ->once()
            ->andReturn([
                'status' => 'warning',
                'message' => 'Pago fallido procesado',
            ]);

        $payload = [
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'inv_test_456',
                    'customer' => 'cus_test_123',
                    'last_payment_error' => [
                        'message' => 'Tarjeta rechazada',
                    ],
                ],
            ],
        ];

        $response = $this->withHeaders([
            'Stripe-Signature' => 'whsec_test_signature',
        ])->postJson('/api/v1/payments/webhook', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'status',
                    'message',
                ],
            ]);

        // Verificar que la factura se actualizó
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'uncollectible',
        ]);
    }

    /** @test */
    public function it_handles_subscription_created_webhook()
    {
        $this->stripeService
            ->shouldReceive('handleWebhook')
            ->once()
            ->andReturn([
                'status' => 'success',
                'message' => 'Suscripción creada',
            ]);

        $payload = [
            'type' => 'customer.subscription.created',
            'data' => [
                'object' => [
                    'id' => 'sub_test_123',
                    'customer' => 'cus_test_123',
                    'status' => 'active',
                    'metadata' => [
                        'plan_type' => 'premium',
                        'user_id' => $this->user->id,
                    ],
                ],
            ],
        ];

        $response = $this->withHeaders([
            'Stripe-Signature' => 'whsec_test_signature',
        ])->postJson('/api/v1/payments/webhook', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'status',
                    'message',
                ],
            ]);
    }

    /** @test */
    public function it_handles_subscription_cancelled_webhook()
    {
        // Crear suscripción de prueba
        $subscription = Subscription::factory()->create([
            'user_id' => $this->user->id,
            'stripe_subscription_id' => 'sub_test_789',
            'status' => 'active',
        ]);

        $this->stripeService
            ->shouldReceive('handleWebhook')
            ->once()
            ->andReturn([
                'status' => 'success',
                'message' => 'Suscripción cancelada',
            ]);

        $payload = [
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'id' => 'sub_test_789',
                    'customer' => 'cus_test_123',
                    'status' => 'canceled',
                ],
            ],
        ];

        $response = $this->withHeaders([
            'Stripe-Signature' => 'whsec_test_signature',
        ])->postJson('/api/v1/payments/webhook', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'status',
                    'message',
                ],
            ]);

        // Verificar que la suscripción se actualizó
        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => 'cancelled',
        ]);
    }

    /** @test */
    public function it_requires_stripe_signature_header()
    {
        $response = $this->postJson('/api/v1/payments/webhook', [
            'type' => 'invoice.payment_succeeded',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Firma de webhook requerida',
            ]);
    }

    /** @test */
    public function it_handles_unknown_webhook_events()
    {
        $this->stripeService
            ->shouldReceive('handleWebhook')
            ->once()
            ->andReturn([
                'status' => 'ignored',
                'message' => 'Evento no manejado',
            ]);

        $payload = [
            'type' => 'unknown.event.type',
            'data' => [
                'object' => [],
            ],
        ];

        $response = $this->withHeaders([
            'Stripe-Signature' => 'whsec_test_signature',
        ])->postJson('/api/v1/payments/webhook', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'ignored',
                    'message' => 'Evento no manejado',
                ],
            ]);
    }

    /** @test */
    public function it_sends_notification_on_successful_payment()
    {
        Notification::fake();

        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'stripe_invoice_id' => 'inv_test_123',
            'status' => 'open',
        ]);

        $this->stripeService
            ->shouldReceive('handleWebhook')
            ->once()
            ->andReturn([
                'status' => 'success',
                'message' => 'Pago procesado correctamente',
            ]);

        $payload = [
            'type' => 'invoice.payment_succeeded',
            'data' => [
                'object' => [
                    'id' => 'inv_test_123',
                    'amount_paid' => 29900,
                ],
            ],
        ];

        $response = $this->withHeaders([
            'Stripe-Signature' => 'whsec_test_signature',
        ])->postJson('/api/v1/payments/webhook', $payload);

        $response->assertStatus(200);

        // Verificar que se envió la notificación
        Notification::assertSentTo(
            $this->user,
            PaymentSuccessful::class
        );
    }

    /** @test */
    public function it_sends_notification_on_failed_payment()
    {
        Notification::fake();

        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'stripe_invoice_id' => 'inv_test_456',
            'status' => 'open',
        ]);

        $this->stripeService
            ->shouldReceive('handleWebhook')
            ->once()
            ->andReturn([
                'status' => 'warning',
                'message' => 'Pago fallido procesado',
            ]);

        $payload = [
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'inv_test_456',
                    'last_payment_error' => [
                        'message' => 'Tarjeta rechazada',
                    ],
                ],
            ],
        ];

        $response = $this->withHeaders([
            'Stripe-Signature' => 'whsec_test_signature',
        ])->postJson('/api/v1/payments/webhook', $payload);

        $response->assertStatus(200);

        // Verificar que se envió la notificación
        Notification::assertSentTo(
            $this->user,
            PaymentFailed::class
        );
    }
} 