<?php

namespace Tests\Feature\Api\Public;

use App\Models\Categoria;
use App\Models\Destino;
use App\Models\Region;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DestinoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear roles y permisos
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    #[Test]
    public function it_can_list_published_destinations()
    {
        // Crear datos de prueba
        $region = Region::factory()->create();
        $categoria = Categoria::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('provider');

        // Crear destinos publicados
        $destino1 = Destino::factory()->create([
            'user_id' => $user->id,
            'region_id' => $region->id,
            'status' => 'published',
        ]);
        $destino1->categorias()->attach($categoria->id);

        $destino2 = Destino::factory()->create([
            'user_id' => $user->id,
            'region_id' => $region->id,
            'status' => 'published',
        ]);
        $destino2->categorias()->attach($categoria->id);

        // Crear un destino no publicado (no debería aparecer)
        $destino3 = Destino::factory()->create([
            'user_id' => $user->id,
            'region_id' => $region->id,
            'status' => 'draft',
        ]);

        // Hacer la petición
        $response = $this->getJson('/api/v1/public/destinos');

        // Verificar la respuesta
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'current_page',
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                            'short_description',
                            'status',
                            'region' => ['id', 'name'],
                            'categorias' => [
                                '*' => ['id', 'name']
                            ],
                        ]
                    ],
                    'per_page',
                    'total'
                ],
                'message'
            ]);

        // Verificar que solo aparecen los destinos publicados
        $this->assertEquals(2, $response->json('data.total'));
        $this->assertCount(2, $response->json('data.data'));
    }

    #[Test]
    public function it_can_filter_destinations_by_region()
    {
        // Crear datos de prueba
        $region1 = Region::factory()->create(['name' => 'Region 1']);
        $region2 = Region::factory()->create(['name' => 'Region 2']);
        $categoria = Categoria::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('provider');

        // Crear destinos en diferentes regiones
        $destino1 = Destino::factory()->create([
            'user_id' => $user->id,
            'region_id' => $region1->id,
            'status' => 'published',
        ]);
        $destino1->categorias()->attach($categoria->id);

        $destino2 = Destino::factory()->create([
            'user_id' => $user->id,
            'region_id' => $region2->id,
            'status' => 'published',
        ]);
        $destino2->categorias()->attach($categoria->id);

        // Filtrar por región 1
        $response = $this->getJson("/api/v1/public/destinos?region_id={$region1->id}");

        // Verificar la respuesta
        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('data.total'));
        $this->assertEquals($region1->id, $response->json('data.data.0.region.id'));
    }

    #[Test]
    public function it_can_filter_destinations_by_category()
    {
        // Crear datos de prueba
        $region = Region::factory()->create();
        $categoria1 = Categoria::factory()->create(['name' => 'Categoria 1']);
        $categoria2 = Categoria::factory()->create(['name' => 'Categoria 2']);
        $user = User::factory()->create();
        $user->assignRole('provider');

        // Crear destinos con diferentes categorías
        $destino1 = Destino::factory()->create([
            'user_id' => $user->id,
            'region_id' => $region->id,
            'status' => 'published',
        ]);
        $destino1->categorias()->attach($categoria1->id);

        $destino2 = Destino::factory()->create([
            'user_id' => $user->id,
            'region_id' => $region->id,
            'status' => 'published',
        ]);
        $destino2->categorias()->attach($categoria2->id);

        // Filtrar por categoría 1
        $response = $this->getJson("/api/v1/public/destinos?category_id={$categoria1->id}");

        // Verificar la respuesta
        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('data.total'));
        $this->assertContains($categoria1->id, collect($response->json('data.data.0.categorias'))->pluck('id'));
    }

    #[Test]
    public function it_can_show_a_published_destination_by_slug()
    {
        // Crear datos de prueba
        $region = Region::factory()->create();
        $categoria = Categoria::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('provider');

        $destino = Destino::factory()->create([
            'user_id' => $user->id,
            'region_id' => $region->id,
            'status' => 'published',
            'slug' => 'test-destination',
        ]);
        $destino->categorias()->attach($categoria->id);

        // Hacer la petición
        $response = $this->getJson('/api/v1/public/destinos/test-destination');

        // Verificar la respuesta
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'short_description',
                    'description',
                    'status',
                    'address',
                    'region' => ['id', 'name'],
                    'categorias' => [
                        '*' => ['id', 'name']
                    ],
                    'user' => ['id', 'name'], // Solo información pública del proveedor
                ],
                'message'
            ]);

        $this->assertEquals('test-destination', $response->json('data.slug'));
    }

    #[Test]
    public function it_returns_404_for_non_published_destination()
    {
        // Crear un destino no publicado
        $region = Region::factory()->create();
        $user = User::factory()->create();
        $user->assignRole('provider');

        $destino = Destino::factory()->create([
            'user_id' => $user->id,
            'region_id' => $region->id,
            'status' => 'draft',
            'slug' => 'draft-destination',
        ]);

        // Intentar acceder al destino
        $response = $this->getJson('/api/v1/public/destinos/draft-destination');

        // Verificar que devuelve 404
        $response->assertStatus(404);
    }

    #[Test]
    public function it_returns_404_for_non_existent_destination()
    {
        // Intentar acceder a un destino que no existe
        $response = $this->getJson('/api/v1/public/destinos/non-existent-destination');

        // Verificar que devuelve 404
        $response->assertStatus(404);
    }
} 