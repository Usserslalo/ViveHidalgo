<?php

namespace Tests\Feature\Api;

use App\Models\Destino;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TopDestinoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear una región de prueba
        $this->region = Region::factory()->create(['name' => 'Región Test']);
        
        // Crear un usuario de prueba
        $this->user = User::factory()->create();
    }

    #[Test]
    public function it_returns_only_top_destinations()
    {
        // Crear destinos TOP
        $topDestino1 = Destino::factory()->create([
            'name' => 'Destino TOP 1',
            'region_id' => $this->region->id,
            'status' => 'published',
            'is_top' => true,
        ]);

        $topDestino2 = Destino::factory()->create([
            'name' => 'Destino TOP 2',
            'region_id' => $this->region->id,
            'status' => 'published',
            'is_top' => true,
        ]);

        // Crear destinos normales (no TOP)
        $normalDestino1 = Destino::factory()->create([
            'name' => 'Destino Normal 1',
            'region_id' => $this->region->id,
            'status' => 'published',
            'is_top' => false,
        ]);

        $normalDestino2 = Destino::factory()->create([
            'name' => 'Destino Normal 2',
            'region_id' => $this->region->id,
            'status' => 'published',
            'is_top' => false,
        ]);

        // Crear destinos TOP pero no publicados
        $draftTopDestino = Destino::factory()->create([
            'name' => 'Destino TOP Borrador',
            'region_id' => $this->region->id,
            'status' => 'draft',
            'is_top' => true,
        ]);

        $response = $this->getJson('/api/v1/public/destinos/top');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'message'
            ])
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['name' => 'Destino TOP 1'])
            ->assertJsonFragment(['name' => 'Destino TOP 2'])
            ->assertJsonMissing(['name' => 'Destino Normal 1'])
            ->assertJsonMissing(['name' => 'Destino Normal 2'])
            ->assertJsonMissing(['name' => 'Destino TOP Borrador']);
    }

    #[Test]
    public function it_returns_empty_array_when_no_top_destinations()
    {
        // Crear solo destinos normales
        Destino::factory()->create([
            'name' => 'Destino Normal 1',
            'region_id' => $this->region->id,
            'status' => 'published',
            'is_top' => false,
        ]);

        Destino::factory()->create([
            'name' => 'Destino Normal 2',
            'region_id' => $this->region->id,
            'status' => 'published',
            'is_top' => false,
        ]);

        $response = $this->getJson('/api/v1/public/destinos/top');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data')
            ->assertJson([
                'success' => true,
                'message' => 'Destinos TOP recuperados con éxito.'
            ]);
    }

    #[Test]
    public function it_respects_limit_parameter()
    {
        // Crear 15 destinos TOP
        for ($i = 1; $i <= 15; $i++) {
            Destino::factory()->create([
                'name' => "Destino TOP {$i}",
                'region_id' => $this->region->id,
                'status' => 'published',
                'is_top' => true,
            ]);
        }

        // Probar con límite de 5
        $response = $this->getJson('/api/v1/public/destinos/top?limit=5');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');

        // Probar con límite de 10 (default)
        $response = $this->getJson('/api/v1/public/destinos/top');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data');

        // Probar con límite mayor a 50 (debería limitar a 50)
        $response = $this->getJson('/api/v1/public/destinos/top?limit=100');

        $response->assertStatus(200)
            ->assertJsonCount(15, 'data'); // Solo hay 15 destinos TOP creados
    }

    #[Test]
    public function it_includes_related_data()
    {
        $topDestino = Destino::factory()->create([
            'name' => 'Destino TOP Completo',
            'region_id' => $this->region->id,
            'status' => 'published',
            'is_top' => true,
        ]);

        $response = $this->getJson('/api/v1/public/destinos/top');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'short_description',
                        'description',
                        'status',
                        'is_top',
                        'is_featured',
                        'region' => [
                            'id',
                            'name',
                            'slug'
                        ],
                        'categorias',
                        'caracteristicas',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'message'
            ])
            ->assertJsonFragment([
                'name' => 'Destino TOP Completo',
                'is_top' => true
            ]);
    }

    #[Test]
    public function it_orders_by_created_at_desc()
    {
        // Crear destinos TOP con fechas específicas
        $oldTopDestino = Destino::factory()->create([
            'name' => 'Destino TOP Antiguo',
            'region_id' => $this->region->id,
            'status' => 'published',
            'is_top' => true,
            'created_at' => now()->subDays(5),
        ]);

        $newTopDestino = Destino::factory()->create([
            'name' => 'Destino TOP Nuevo',
            'region_id' => $this->region->id,
            'status' => 'published',
            'is_top' => true,
            'created_at' => now()->subDays(1),
        ]);

        $response = $this->getJson('/api/v1/public/destinos/top');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $data = $response->json('data');
        
        // Verificar que el más reciente aparece primero
        $this->assertEquals('Destino TOP Nuevo', $data[0]['name']);
        $this->assertEquals('Destino TOP Antiguo', $data[1]['name']);
    }

    #[Test]
    public function it_excludes_draft_and_pending_destinations()
    {
        // Crear destinos TOP en diferentes estados
        $publishedTop = Destino::factory()->create([
            'name' => 'Destino TOP Publicado',
            'region_id' => $this->region->id,
            'status' => 'published',
            'is_top' => true,
        ]);

        $draftTop = Destino::factory()->create([
            'name' => 'Destino TOP Borrador',
            'region_id' => $this->region->id,
            'status' => 'draft',
            'is_top' => true,
        ]);

        $pendingTop = Destino::factory()->create([
            'name' => 'Destino TOP Pendiente',
            'region_id' => $this->region->id,
            'status' => 'pending_review',
            'is_top' => true,
        ]);

        $response = $this->getJson('/api/v1/public/destinos/top');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Destino TOP Publicado'])
            ->assertJsonMissing(['name' => 'Destino TOP Borrador'])
            ->assertJsonMissing(['name' => 'Destino TOP Pendiente']);
    }

    #[Test]
    public function it_works_with_factory_top_state()
    {
        // Usar el estado 'top' del factory
        $topDestino = Destino::factory()->top()->published()->create([
            'name' => 'Destino TOP con Factory',
            'region_id' => $this->region->id,
        ]);

        $response = $this->getJson('/api/v1/public/destinos/top');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'name' => 'Destino TOP con Factory',
                'is_top' => true
            ]);
    }

    #[Test]
    public function it_returns_correct_response_structure()
    {
        Destino::factory()->top()->published()->create([
            'name' => 'Destino TOP Test',
            'region_id' => $this->region->id,
        ]);

        $response = $this->getJson('/api/v1/public/destinos/top');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Destinos TOP recuperados con éxito.'
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'short_description',
                        'description',
                        'status',
                        'is_top',
                        'is_featured',
                        'region',
                        'categorias',
                        'caracteristicas',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'message'
            ]);
    }
} 