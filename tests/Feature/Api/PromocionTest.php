<?php

namespace Tests\Feature\Api;

use App\Models\Destino;
use App\Models\Promocion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PromocionTest extends TestCase
{
    use RefreshDatabase;

    private $provider;
    private $destino;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = User::factory()->create();
        $this->destino = Destino::factory()->create(['user_id' => $this->provider->id]);
    }

    #[Test]
    public function it_returns_only_active_and_current_promotions()
    {
        // Activa y vigente
        Promocion::factory()->create([
            'destino_id' => $this->destino->id,
            'is_active' => true,
            'start_date' => Carbon::now()->subDay(),
            'end_date' => Carbon::now()->addDay(),
        ]);

        // Activa pero futura
        Promocion::factory()->create([
            'destino_id' => $this->destino->id,
            'is_active' => true,
            'start_date' => Carbon::now()->addDay(),
            'end_date' => Carbon::now()->addDays(2),
        ]);

        // Activa pero expirada
        Promocion::factory()->create([
            'destino_id' => $this->destino->id,
            'is_active' => true,
            'start_date' => Carbon::now()->subDays(2),
            'end_date' => Carbon::now()->subDay(),
        ]);

        // Inactiva pero vigente
        Promocion::factory()->create([
            'destino_id' => $this->destino->id,
            'is_active' => false,
            'start_date' => Carbon::now()->subDay(),
            'end_date' => Carbon::now()->addDay(),
        ]);

        $response = $this->getJson('/api/v1/public/promociones');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data');
    }

    #[Test]
    public function it_returns_promotions_for_a_specific_destino()
    {
        $otroDestino = Destino::factory()->create();

        // Promoción para el destino principal
        Promocion::factory()->create([
            'destino_id' => $this->destino->id,
            'is_active' => true,
            'start_date' => Carbon::now()->subDay(),
            'end_date' => Carbon::now()->addDay(),
        ]);

        // Promoción para otro destino
        Promocion::factory()->create([
            'destino_id' => $otroDestino->id,
            'is_active' => true,
            'start_date' => Carbon::now()->subDay(),
            'end_date' => Carbon::now()->addDay(),
        ]);

        $response = $this->getJson("/api/v1/public/destinos/{$this->destino->id}/promociones");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
        
        $this->assertEquals($this->destino->id, $response->json('data.0.destino_id'));
    }

    #[Test]
    public function it_returns_an_empty_list_if_no_promotions_are_current()
    {
        Promocion::factory()->create([
            'destino_id' => $this->destino->id,
            'is_active' => true,
            'start_date' => Carbon::now()->subDays(2),
            'end_date' => Carbon::now()->subDay(),
        ]);

        $response = $this->getJson('/api/v1/public/promociones');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data.data');
    }
} 