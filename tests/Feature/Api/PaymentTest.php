<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Subscription;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Mockery;

class PaymentTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $stripeService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->user->assignRole('provider');
        
        // Mock StripeService
        $this->stripeService = Mockery::mock(StripeService::class);
        $this->app->instance(StripeService::class, $this->stripeService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_create_checkout_session()
    {
        $this->stripeService
            ->shouldReceive('createCheckoutSession')
            ->once()
            ->andReturn([
                'session_id' => 'cs_test_123',
                'checkout_url' => 'https://checkout.stripe.com/test',
                'invoice_id' => 1,
                'amount' => 599.00,
                'currency' => 'mxn',
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payments/create-checkout-session', [
                'plan_type' => 'premium',
                'billing_cycle' => 'monthly',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'session_id',
                    'checkout_url',
                    'invoice_id',
                    'amount',
                    'currency',
                ],
            ]);
    }

    /** @test */
    public function it_validates_plan_type_when_creating_checkout_session()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payments/create-checkout-session', [
                'plan_type' => 'invalid_plan',
            ]);

        $response->assertStatus(422);
        $this->assertArrayHasKey('plan_type', $response->json('errors'));
    }

    /** @test */
    public function it_requires_provider_role_to_create_checkout_session()
    {
        $tourist = User::factory()->create();
        $tourist->assignRole('tourist');

        $response = $this->actingAs($tourist, 'sanctum')
            ->postJson('/api/v1/payments/create-checkout-session', [
                'plan_type' => 'premium',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_get_user_invoices()
    {
        // Crear facturas para el usuario con metadata correcto
        Invoice::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'metadata' => [
                'plan_type' => 'premium',
                'billing_cycle' => 'monthly',
                'session_id' => 'cs_test_123',
            ],
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/payments/invoices');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'invoices',
                    'pagination' => [
                        'current_page',
                        'per_page',
                        'total',
                        'last_page',
                    ],
                    'stats' => [
                        'total_invoices',
                        'paid_invoices',
                        'unpaid_invoices',
                        'overdue_invoices',
                    ],
                ],
            ]);

        $this->assertEquals(5, $response->json('data.pagination.total'));
    }

    /** @test */
    public function it_can_filter_invoices_by_status()
    {
        // Crear facturas con diferentes estados y metadata correcto
        Invoice::factory()->paid()->create([
            'user_id' => $this->user->id,
            'metadata' => ['plan_type' => 'basic'],
        ]);
        Invoice::factory()->unpaid()->create([
            'user_id' => $this->user->id,
            'metadata' => ['plan_type' => 'premium'],
        ]);
        Invoice::factory()->overdue()->create([
            'user_id' => $this->user->id,
            'metadata' => ['plan_type' => 'enterprise'],
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/payments/invoices?status=paid');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('data.pagination.total'));
    }

    /** @test */
    public function it_can_get_user_payment_methods()
    {
        // Crear métodos de pago para el usuario con metadata correcto
        PaymentMethod::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'metadata' => [
                'fingerprint' => 'test_fingerprint',
                'country' => 'MX',
            ],
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/payments/payment-methods');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'stripe_payment_method_id',
                        'type',
                        'last4',
                        'brand',
                        'is_default',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function it_can_update_payment_method()
    {
        $this->user->update(['stripe_customer_id' => 'cus_test_123']);

        $this->stripeService
            ->shouldReceive('updatePaymentMethod')
            ->once()
            ->andReturn(PaymentMethod::factory()->create([
                'user_id' => $this->user->id,
                'is_default' => true,
                'metadata' => ['fingerprint' => 'test'],
            ]));

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payments/update-payment-method', [
                'payment_method_id' => 'pm_test_123',
            ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_validates_payment_method_id_format()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payments/update-payment-method', [
                'payment_method_id' => 'invalid_id',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_can_delete_payment_method()
    {
        // Crear múltiples métodos de pago para que se pueda eliminar uno
        PaymentMethod::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'metadata' => ['fingerprint' => 'test'],
        ]);

        $paymentMethod = PaymentMethod::where('user_id', $this->user->id)->first();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/payments/payment-methods/{$paymentMethod->id}");

        $response->assertStatus(200);
        
        // Verificar que el método de pago fue soft deleted
        $this->assertSoftDeleted('payment_methods', ['id' => $paymentMethod->id]);
    }

    /** @test */
    public function it_cannot_delete_only_payment_method()
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'user_id' => $this->user->id,
            'metadata' => ['fingerprint' => 'test'],
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/payments/payment-methods/{$paymentMethod->id}");

        $response->assertStatus(422);
    }

    /** @test */
    public function it_cannot_delete_other_users_payment_method()
    {
        $otherUser = User::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create([
            'user_id' => $otherUser->id,
            'metadata' => ['fingerprint' => 'test'],
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/payments/payment-methods/{$paymentMethod->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_handle_stripe_webhook()
    {
        $this->stripeService
            ->shouldReceive('handleWebhook')
            ->once()
            ->andReturn(['status' => 'success']);

        $payload = [
            'type' => 'invoice.payment_succeeded',
            'data' => ['object' => ['id' => 'in_test_123']],
        ];

        $response = $this->postJson('/api/v1/payments/webhook', $payload, [
            'Stripe-Signature' => 'test_signature',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_requires_signature_for_webhook()
    {
        $response = $this->postJson('/api/v1/payments/webhook', []);

        $response->assertStatus(400);
    }

    /** @test */
    public function it_prevents_duplicate_active_subscriptions()
    {
        // Crear suscripción activa existente
        Subscription::factory()->active()->create([
            'user_id' => $this->user->id,
            'features' => ['destinos_limit' => 5],
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payments/create-checkout-session', [
                'plan_type' => 'premium',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_requires_stripe_customer_id_for_payment_method_update()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/payments/update-payment-method', [
                'payment_method_id' => 'pm_test_123',
            ]);

        $response->assertStatus(422);
    }
} 