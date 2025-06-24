<?php

namespace Tests\Feature\Api;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ejecutar seeders necesarios
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    #[Test]
    public function user_can_get_available_plans()
    {
        $response = $this->getJson('/api/v1/subscriptions/plans');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'plans' => [
                            'basic',
                            'premium',
                            'enterprise'
                        ]
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertArrayHasKey('basic', $response->json('data.plans'));
        $this->assertArrayHasKey('premium', $response->json('data.plans'));
        $this->assertArrayHasKey('enterprise', $response->json('data.plans'));
    }

    #[Test]
    public function provider_can_get_their_subscription()
    {
        $provider = User::factory()->create();
        $provider->assignRole('provider');

        $subscription = Subscription::factory()->active()->create([
            'user_id' => $provider->id,
        ]);

        Sanctum::actingAs($provider);

        $response = $this->getJson('/api/v1/subscriptions/my-subscription');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'subscription',
                        'plan_config',
                        'subscription_stats',
                        'plan_limits'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals($subscription->id, $response->json('data.subscription.id'));
    }

    #[Test]
    public function provider_without_subscription_gets_404()
    {
        $provider = User::factory()->create();
        $provider->assignRole('provider');

        Sanctum::actingAs($provider);

        $response = $this->getJson('/api/v1/subscriptions/my-subscription');

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'No se encontró suscripción'
                ]);
    }

    #[Test]
    public function tourist_cannot_access_subscription_endpoints()
    {
        $tourist = User::factory()->create();
        $tourist->assignRole('tourist');

        Sanctum::actingAs($tourist);

        $response = $this->getJson('/api/v1/subscriptions/my-subscription');

        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'Acceso denegado'
                ]);
    }

    #[Test]
    public function provider_can_subscribe_to_plan()
    {
        $provider = User::factory()->create();
        $provider->assignRole('provider');

        Sanctum::actingAs($provider);

        $subscriptionData = [
            'plan_type' => Subscription::PLAN_PREMIUM,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'auto_renew' => true,
            'payment_method' => 'credit_card',
            'transaction_id' => 'txn_test_123',
        ];

        $response = $this->postJson('/api/v1/subscriptions/subscribe', $subscriptionData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'user_id',
                        'plan_type',
                        'status',
                        'amount',
                        'billing_cycle',
                        'auto_renew',
                        'payment_status',
                        'transaction_id',
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals(Subscription::PLAN_PREMIUM, $response->json('data.plan_type'));
        $this->assertEquals(Subscription::STATUS_ACTIVE, $response->json('data.status'));
        $this->assertEquals(Subscription::PAYMENT_COMPLETED, $response->json('data.payment_status'));

        // Verificar que se creó en la base de datos
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $provider->id,
            'plan_type' => Subscription::PLAN_PREMIUM,
            'status' => Subscription::STATUS_ACTIVE,
        ]);
    }

    #[Test]
    public function provider_cannot_subscribe_if_already_has_active_subscription()
    {
        $provider = User::factory()->create();
        $provider->assignRole('provider');

        // Crear suscripción activa existente
        Subscription::factory()->active()->create([
            'user_id' => $provider->id,
        ]);

        Sanctum::actingAs($provider);

        $subscriptionData = [
            'plan_type' => Subscription::PLAN_BASIC,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
        ];

        $response = $this->postJson('/api/v1/subscriptions/subscribe', $subscriptionData);

        $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Suscripción existente'
                ]);
    }

    #[Test]
    public function provider_can_cancel_subscription()
    {
        $provider = User::factory()->create();
        $provider->assignRole('provider');

        $subscription = Subscription::factory()->active()->create([
            'user_id' => $provider->id,
        ]);

        Sanctum::actingAs($provider);

        $response = $this->putJson('/api/v1/subscriptions/cancel');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Suscripción cancelada exitosamente'
                ]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => Subscription::STATUS_CANCELLED,
            'auto_renew' => false,
        ]);
    }

    #[Test]
    public function provider_cannot_cancel_nonexistent_subscription()
    {
        $provider = User::factory()->create();
        $provider->assignRole('provider');

        Sanctum::actingAs($provider);

        $response = $this->putJson('/api/v1/subscriptions/cancel');

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'No se encontró suscripción'
                ]);
    }

    #[Test]
    public function provider_can_renew_subscription()
    {
        $provider = User::factory()->create();
        $provider->assignRole('provider');

        $subscription = Subscription::factory()->create([
            'user_id' => $provider->id,
            'status' => Subscription::STATUS_CANCELLED,
            'auto_renew' => false,
        ]);

        Sanctum::actingAs($provider);

        $response = $this->putJson('/api/v1/subscriptions/renew', [
            'transaction_id' => 'txn_renew_123',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Suscripción renovada exitosamente'
                ]);

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => Subscription::STATUS_ACTIVE,
            'auto_renew' => true,
            'payment_status' => Subscription::PAYMENT_COMPLETED,
        ]);
    }

    #[Test]
    public function provider_can_get_plan_limits()
    {
        $provider = User::factory()->create();
        $provider->assignRole('provider');

        $subscription = Subscription::factory()->active()->basic()->create([
            'user_id' => $provider->id,
        ]);

        Sanctum::actingAs($provider);

        $response = $this->getJson('/api/v1/subscriptions/limits');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'plan_limits',
                        'subscription_stats'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertArrayHasKey('destinos_limit', $response->json('data.plan_limits'));
        $this->assertArrayHasKey('promociones_limit', $response->json('data.plan_limits'));
        $this->assertArrayHasKey('can_create_destino', $response->json('data.plan_limits'));
        $this->assertArrayHasKey('can_create_promocion', $response->json('data.plan_limits'));
    }

    #[Test]
    public function subscription_validation_works_correctly()
    {
        $provider = User::factory()->create();
        $provider->assignRole('provider');

        Sanctum::actingAs($provider);

        // Test con datos inválidos
        $invalidData = [
            'plan_type' => 'invalid_plan',
            'billing_cycle' => 'invalid_cycle',
        ];

        $response = $this->postJson('/api/v1/subscriptions/subscribe', $invalidData);

        $response->assertStatus(400)
                ->assertJsonValidationErrors(['plan_type', 'billing_cycle']);
    }

    #[Test]
    public function subscription_pricing_is_calculated_correctly()
    {
        // Verificar precios de diferentes planes y ciclos
        $basicMonthly = Subscription::calculatePrice(Subscription::PLAN_BASIC, Subscription::CYCLE_MONTHLY);
        $basicYearly = Subscription::calculatePrice(Subscription::PLAN_BASIC, Subscription::CYCLE_YEARLY);
        
        $premiumMonthly = Subscription::calculatePrice(Subscription::PLAN_PREMIUM, Subscription::CYCLE_MONTHLY);
        $premiumYearly = Subscription::calculatePrice(Subscription::PLAN_PREMIUM, Subscription::CYCLE_YEARLY);

        $this->assertEquals(99.99, $basicMonthly);
        $this->assertEquals(999.99, $basicYearly);
        $this->assertEquals(299.99, $premiumMonthly);
        $this->assertEquals(2999.99, $premiumYearly);
    }

    #[Test]
    public function subscription_model_methods_work_correctly()
    {
        $subscription = Subscription::factory()->create([
            'status' => Subscription::STATUS_ACTIVE,
            'end_date' => now()->addDays(30),
        ]);

        // Test isActive
        $this->assertTrue($subscription->isActive());

        // Test isExpired
        $expiredSubscription = Subscription::factory()->create([
            'status' => Subscription::STATUS_EXPIRED,
            'end_date' => now()->subDays(1),
        ]);
        $this->assertTrue($expiredSubscription->isExpired());

        // Test isExpiringSoon
        $expiringSoonSubscription = Subscription::factory()->create([
            'status' => Subscription::STATUS_ACTIVE,
            'end_date' => now()->addDays(5),
        ]);
        $this->assertTrue($expiringSoonSubscription->isExpiringSoon());

        // Test days remaining
        $this->assertGreaterThan(0, $subscription->days_remaining);
        $this->assertEquals(0, $expiredSubscription->days_remaining);
    }

    #[Test]
    public function user_model_subscription_methods_work_correctly()
    {
        $provider = User::factory()->create();
        $provider->assignRole('provider');

        // Sin suscripción
        $this->assertFalse($provider->hasActiveSubscription());
        $this->assertNull($provider->getActiveSubscription());

        // Con suscripción activa
        $subscription = Subscription::factory()->active()->create([
            'user_id' => $provider->id,
        ]);

        $provider->refresh();
        $this->assertTrue($provider->hasActiveSubscription());
        $this->assertNotNull($provider->getActiveSubscription());
        $this->assertEquals($subscription->id, $provider->getActiveSubscription()->id);

        // Test límites del plan
        $limits = $provider->plan_limits;
        $this->assertArrayHasKey('destinos_limit', $limits);
        $this->assertArrayHasKey('promociones_limit', $limits);
        $this->assertArrayHasKey('can_create_destino', $limits);
        $this->assertArrayHasKey('can_create_promocion', $limits);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_subscription_endpoints()
    {
        $endpoints = [
            'GET /api/v1/subscriptions/my-subscription',
            'POST /api/v1/subscriptions/subscribe',
            'PUT /api/v1/subscriptions/cancel',
            'PUT /api/v1/subscriptions/renew',
            'GET /api/v1/subscriptions/limits',
        ];

        foreach ($endpoints as $endpoint) {
            [$method, $url] = explode(' ', $endpoint);
            
            $response = $this->json($method, $url);
            $response->assertStatus(401);
        }
    }
} 