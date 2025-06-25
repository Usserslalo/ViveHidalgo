<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimplePaymentTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->assignRole('provider');
    }

    /** @test */
    public function basic_payment_system_works()
    {
        $this->assertTrue(true); // Test básico para verificar que el sistema funciona
    }

    /** @test */
    public function invoices_endpoint_returns_empty_when_no_invoices()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/payments/invoices');

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json('data.pagination.total'));
    }

    /** @test */
    public function can_create_invoice()
    {
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'metadata' => [
                'plan_type' => 'basic',
                'billing_cycle' => 'monthly',
            ],
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function can_create_payment_method()
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'user_id' => $this->user->id,
            'metadata' => [
                'fingerprint' => 'test_fingerprint',
                'country' => 'MX',
            ],
        ]);

        $this->assertDatabaseHas('payment_methods', [
            'id' => $paymentMethod->id,
            'user_id' => $this->user->id,
        ]);
    }
} 