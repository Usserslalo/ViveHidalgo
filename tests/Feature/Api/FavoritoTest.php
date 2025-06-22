<?php

namespace Tests\Feature\Api;

use App\Models\Destino;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Spatie\Permission\Models\Role;

class FavoritoTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Destino $destino;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear rol tourist si no existe
        Role::firstOrCreate(['name' => 'tourist']);
        
        // Crear usuario de prueba
        $this->user = User::factory()->create();
        $this->user->assignRole('tourist');
        
        // Crear destino de prueba
        $this->destino = Destino::factory()->create([
            'status' => 'published',
            'user_id' => User::factory()->create()->id,
        ]);
    }

    /** @test */
    public function user_can_add_destination_to_favorites()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/user/favoritos/{$this->destino->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Destino añadido a favoritos correctamente'
                ]);

        $this->assertTrue($this->user->favoritos()->where('destino_id', $this->destino->id)->exists());
    }

    /** @test */
    public function user_cannot_add_same_destination_twice_to_favorites()
    {
        Sanctum::actingAs($this->user);

        // Añadir por primera vez
        $this->user->favoritos()->attach($this->destino->id);

        // Intentar añadir de nuevo
        $response = $this->postJson("/api/v1/user/favoritos/{$this->destino->id}");

        $response->assertStatus(409)
                ->assertJson([
                    'success' => false,
                    'message' => 'El destino ya está en tus favoritos'
                ]);
    }

    /** @test */
    public function user_cannot_add_unpublished_destination_to_favorites()
    {
        Sanctum::actingAs($this->user);

        $unpublishedDestino = Destino::factory()->create([
            'status' => 'draft',
            'user_id' => User::factory()->create()->id,
        ]);

        $response = $this->postJson("/api/v1/user/favoritos/{$unpublishedDestino->id}");

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Destino no encontrado o no disponible'
                ]);
    }

    /** @test */
    public function user_can_remove_destination_from_favorites()
    {
        Sanctum::actingAs($this->user);

        // Añadir a favoritos primero
        $this->user->favoritos()->attach($this->destino->id);

        $response = $this->deleteJson("/api/v1/user/favoritos/{$this->destino->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Destino removido de favoritos correctamente'
                ]);

        $this->assertFalse($this->user->favoritos()->where('destino_id', $this->destino->id)->exists());
    }

    /** @test */
    public function user_cannot_remove_destination_not_in_favorites()
    {
        Sanctum::actingAs($this->user);

        $response = $this->deleteJson("/api/v1/user/favoritos/{$this->destino->id}");

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'El destino no está en tus favoritos'
                ]);
    }

    /** @test */
    public function user_can_get_their_favorites_list()
    {
        Sanctum::actingAs($this->user);

        // Añadir varios destinos a favoritos
        $destinos = Destino::factory()->count(3)->create([
            'status' => 'published',
            'user_id' => User::factory()->create()->id,
        ]);

        foreach ($destinos as $destino) {
            $this->user->favoritos()->attach($destino->id);
        }

        $response = $this->getJson('/api/v1/user/favoritos');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Lista de favoritos recuperada correctamente'
                ])
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
                                'region',
                                'categorias',
                                'caracteristicas'
                            ]
                        ],
                        'total'
                    ],
                    'message'
                ]);

        $this->assertEquals(3, $response->json('data.total'));
    }

    /** @test */
    public function user_can_check_if_destination_is_in_favorites()
    {
        Sanctum::actingAs($this->user);

        // Verificar que no está en favoritos
        $response = $this->getJson("/api/v1/user/favoritos/check/{$this->destino->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'is_favorite' => false
                    ]
                ]);

        // Añadir a favoritos
        $this->user->favoritos()->attach($this->destino->id);

        // Verificar que ahora está en favoritos
        $response = $this->getJson("/api/v1/user/favoritos/check/{$this->destino->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'is_favorite' => true
                    ]
                ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_favorites_endpoints()
    {
        // Intentar añadir a favoritos sin autenticación
        $response = $this->postJson("/api/v1/user/favoritos/{$this->destino->id}");
        $response->assertStatus(401);

        // Intentar obtener favoritos sin autenticación
        $response = $this->getJson('/api/v1/user/favoritos');
        $response->assertStatus(401);

        // Intentar remover de favoritos sin autenticación
        $response = $this->deleteJson("/api/v1/user/favoritos/{$this->destino->id}");
        $response->assertStatus(401);

        // Intentar verificar favorito sin autenticación
        $response = $this->getJson("/api/v1/user/favoritos/check/{$this->destino->id}");
        $response->assertStatus(401);
    }

    /** @test */
    public function favorites_list_only_shows_published_destinations()
    {
        Sanctum::actingAs($this->user);

        // Crear destinos con diferentes estados
        $publishedDestino = Destino::factory()->create([
            'status' => 'published',
            'user_id' => User::factory()->create()->id,
        ]);

        $draftDestino = Destino::factory()->create([
            'status' => 'draft',
            'user_id' => User::factory()->create()->id,
        ]);

        // Añadir ambos a favoritos
        $this->user->favoritos()->attach([$publishedDestino->id, $draftDestino->id]);

        $response = $this->getJson('/api/v1/user/favoritos');

        $response->assertStatus(200);
        
        // Solo debería mostrar el destino publicado
        $this->assertEquals(1, $response->json('data.total'));
        $this->assertEquals($publishedDestino->id, $response->json('data.data.0.id'));
    }

    /** @test */
    public function favorites_list_includes_related_data()
    {
        Sanctum::actingAs($this->user);

        $this->user->favoritos()->attach($this->destino->id);

        $response = $this->getJson('/api/v1/user/favoritos');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'data' => [
                            '*' => [
                                'region',
                                'categorias',
                                'caracteristicas'
                            ]
                        ]
                    ]
                ]);
    }
} 