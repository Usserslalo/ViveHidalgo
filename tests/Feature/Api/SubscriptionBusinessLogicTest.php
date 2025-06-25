<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Subscription;
use App\Models\PaymentMethod;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class SubscriptionBusinessLogicTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected User $provider;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->provider = User::factory()->create()->assignRole('provider');
    }

    /** @test */
    public function user_cannot_subscribe_without_payment_method()
    {
        Sanctum::actingAs($this->provider);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('El usuario debe tener un método de pago configurado');

        $this->provider->subscribeToPlan('basic');
    }

    /** @test */
    public function user_cannot_subscribe_if_already_has_active_subscription()
    {
        Sanctum::actingAs($this->provider);

        // Crear método de pago
        PaymentMethod::factory()->create([
            'user_id' => $this->provider->id,
            'is_default' => true,
        ]);

        // Crear suscripción activa
        Subscription::factory()->create([
            'user_id' => $this->provider->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('El usuario ya tiene una suscripción activa');

        $this->provider->subscribeToPlan('basic');
    }

    /** @test */
    public function user_cannot_subscribe_if_not_provider()
    {
        Sanctum::actingAs($this->user);

        // Crear método de pago
        PaymentMethod::factory()->create([
            'user_id' => $this->user->id,
            'is_default' => true,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Solo los proveedores pueden suscribirse a planes');

        $this->user->subscribeToPlan('basic');
    }

    /** @test */
    public function user_cannot_cancel_subscription_if_none_exists()
    {
        Sanctum::actingAs($this->provider);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No hay suscripción activa para cancelar');

        $this->provider->cancelSubscription();
    }

    /** @test */
    public function user_can_subscribe_with_valid_payment_method()
    {
        Sanctum::actingAs($this->provider);

        // Crear método de pago
        PaymentMethod::factory()->create([
            'user_id' => $this->provider->id,
            'is_default' => true,
        ]);

        // Mock del StripeService
        $this->mock(\App\Services\StripeService::class, function ($mock) {
            $mock->shouldReceive('createCheckoutSession')
                ->once()
                ->andReturn([
                    'session_id' => 'cs_test_123',
                    'checkout_url' => 'https://checkout.stripe.com/test',
                    'invoice_id' => 1,
                    'amount' => 99.99,
                    'currency' => 'MXN',
                ]);
        });

        $result = $this->provider->subscribeToPlan('basic');

        $this->assertArrayHasKey('session_id', $result);
        $this->assertArrayHasKey('checkout_url', $result);
    }

    /** @test */
    public function user_can_cancel_active_subscription()
    {
        Sanctum::actingAs($this->provider);

        // Crear suscripción activa
        $subscription = Subscription::factory()->create([
            'user_id' => $this->provider->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $result = $this->provider->cancelSubscription();

        $this->assertTrue($result);
        $this->assertEquals(Subscription::STATUS_CANCELLED, $subscription->fresh()->status);
    }

    /** @test */
    public function user_can_renew_expired_subscription()
    {
        Sanctum::actingAs($this->provider);

        // Crear suscripción expirada
        $subscription = Subscription::factory()->create([
            'user_id' => $this->provider->id,
            'status' => Subscription::STATUS_EXPIRED,
            'end_date' => now()->subDays(1),
        ]);

        $result = $this->provider->renewSubscription();

        $this->assertTrue($result);
        $this->assertEquals(Subscription::STATUS_ACTIVE, $subscription->fresh()->status);
    }

    /** @test */
    public function user_cannot_renew_active_subscription()
    {
        Sanctum::actingAs($this->provider);

        // Crear suscripción activa
        Subscription::factory()->create([
            'user_id' => $this->provider->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No hay suscripción para renovar o ya está activa');

        $this->provider->renewSubscription();
    }

    /** @test */
    public function user_can_create_destino_within_plan_limits()
    {
        Sanctum::actingAs($this->provider);

        // Crear suscripción activa con límite de 5 destinos
        Subscription::factory()->create([
            'user_id' => $this->provider->id,
            'status' => Subscription::STATUS_ACTIVE,
            'plan_type' => 'basic',
        ]);

        // Crear 3 destinos (dentro del límite)
        for ($i = 0; $i < 3; $i++) {
            \App\Models\Destino::factory()->create([
                'user_id' => $this->provider->id,
            ]);
        }

        $this->assertTrue($this->provider->canCreateDestino());
    }

    /** @test */
    public function user_cannot_create_destino_beyond_plan_limits()
    {
        Sanctum::actingAs($this->provider);

        // Crear suscripción activa con límite de 5 destinos
        Subscription::factory()->create([
            'user_id' => $this->provider->id,
            'status' => Subscription::STATUS_ACTIVE,
            'plan_type' => 'basic',
        ]);

        // Crear 5 destinos (al límite)
        for ($i = 0; $i < 5; $i++) {
            \App\Models\Destino::factory()->create([
                'user_id' => $this->provider->id,
            ]);
        }

        $this->assertFalse($this->provider->canCreateDestino());
    }

    /** @test */
    public function user_can_create_promocion_within_plan_limits()
    {
        Sanctum::actingAs($this->provider);

        // Crear suscripción activa con límite de 2 promociones
        Subscription::factory()->create([
            'user_id' => $this->provider->id,
            'status' => Subscription::STATUS_ACTIVE,
            'plan_type' => 'basic',
        ]);

        // Crear 1 promoción (dentro del límite)
        \App\Models\Promocion::factory()->create([
            'destino_id' => \App\Models\Destino::factory()->create([
                'user_id' => $this->provider->id,
            ])->id,
        ]);

        $this->assertTrue($this->provider->canCreatePromocion());
    }

    /** @test */
    public function user_cannot_create_promocion_beyond_plan_limits()
    {
        Sanctum::actingAs($this->provider);

        // Crear suscripción activa con límite de 2 promociones
        Subscription::factory()->create([
            'user_id' => $this->provider->id,
            'status' => Subscription::STATUS_ACTIVE,
            'plan_type' => 'basic',
        ]);

        // Crear 2 promociones (al límite)
        for ($i = 0; $i < 2; $i++) {
            \App\Models\Promocion::factory()->create([
                'destino_id' => \App\Models\Destino::factory()->create([
                    'user_id' => $this->provider->id,
                ])->id,
            ]);
        }

        $this->assertFalse($this->provider->canCreatePromocion());
    }

    /** @test */
    public function subscription_stats_are_correct()
    {
        Sanctum::actingAs($this->provider);

        // Crear suscripción activa
        Subscription::factory()->create([
            'user_id' => $this->provider->id,
            'status' => Subscription::STATUS_ACTIVE,
            'plan_type' => 'basic',
        ]);

        // Crear algunos destinos y promociones
        for ($i = 0; $i < 3; $i++) {
            \App\Models\Destino::factory()->create([
                'user_id' => $this->provider->id,
            ]);
        }

        for ($i = 0; $i < 1; $i++) {
            \App\Models\Promocion::factory()->create([
                'destino_id' => \App\Models\Destino::factory()->create([
                    'user_id' => $this->provider->id,
                ])->id,
            ]);
        }

        $stats = $this->provider->subscription_stats;

        $this->assertTrue($stats['has_subscription']);
        $this->assertEquals('Plan Básico', $stats['plan_name']);
        $this->assertEquals(3, $stats['destinos_used']);
        $this->assertEquals(5, $stats['destinos_limit']);
        $this->assertEquals(1, $stats['promociones_used']);
        $this->assertEquals(2, $stats['promociones_limit']);
        $this->assertTrue($stats['can_create_destinos']);
        $this->assertTrue($stats['can_create_promociones']);
    }

    /** @test */
    public function plan_limits_are_correct()
    {
        Sanctum::actingAs($this->provider);

        // Crear suscripción activa
        Subscription::factory()->create([
            'user_id' => $this->provider->id,
            'status' => Subscription::STATUS_ACTIVE,
            'plan_type' => 'premium',
        ]);

        $limits = $this->provider->plan_limits;

        $this->assertEquals(15, $limits['destinos_limit']);
        $this->assertEquals(10, $limits['promociones_limit']);
        $this->assertTrue($limits['analytics_advanced']);
        $this->assertTrue($limits['support_phone']);
    }

    /** @test */
    public function user_without_subscription_has_no_limits()
    {
        Sanctum::actingAs($this->provider);

        $limits = $this->provider->plan_limits;

        $this->assertEquals(0, $limits['destinos_limit']);
        $this->assertEquals(0, $limits['promociones_limit']);
        $this->assertFalse($limits['analytics_basic']);
        $this->assertFalse($limits['support_email']);
    }

    /** @test */
    public function stripe_customer_is_created_correctly()
    {
        Sanctum::actingAs($this->provider);

        // Mock del StripeService
        $this->mock(\App\Services\StripeService::class, function ($mock) {
            $mock->shouldReceive('createCustomer')
                ->once()
                ->andReturn((object) ['id' => 'cus_test_123']);

            $mock->shouldReceive('getCustomer')
                ->never();
        });

        $customer = $this->provider->createOrGetStripeCustomer();

        $this->assertEquals('cus_test_123', $customer->id);
        $this->assertEquals('cus_test_123', $this->provider->fresh()->stripe_customer_id);
    }

    /** @test */
    public function existing_stripe_customer_is_retrieved()
    {
        Sanctum::actingAs($this->provider);

        $this->provider->update(['stripe_customer_id' => 'cus_existing_123']);

        // Mock del StripeService
        $this->mock(\App\Services\StripeService::class, function ($mock) {
            $mock->shouldReceive('getCustomer')
                ->once()
                ->with('cus_existing_123')
                ->andReturn((object) ['id' => 'cus_existing_123']);

            $mock->shouldReceive('createCustomer')
                ->never();
        });

        $customer = $this->provider->createOrGetStripeCustomer();

        $this->assertEquals('cus_existing_123', $customer->id);
    }
} 