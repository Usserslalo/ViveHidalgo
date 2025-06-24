<?php

namespace Tests\Feature\Api\Public;

use App\Models\Destino;
use App\Models\Promocion;
use App\Models\Region;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear roles y permisos
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    #[Test]
    public function it_can_get_home_data()
    {
        // Crear datos de prueba
        $user = User::factory()->create();
        $user->assignRole('provider');

        $region = Region::factory()->create();
        $tag = Tag::factory()->create();

        // Crear destinos top
        $topDestino = Destino::factory()->create([
            'user_id' => $user->id,
            'region_id' => $region->id,
            'status' => 'published',
            'is_top' => true,
            'average_rating' => 4.5,
        ]);

        // Crear promoción activa
        $promocion = Promocion::factory()->create([
            'destino_id' => $topDestino->id,
            'is_active' => true,
        ]);

        // Crear tag popular
        $popularTag = Tag::factory()->create();

        // Hacer la petición
        $response = $this->getJson('/api/v1/public/home');

        // Verificar respuesta
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'top_destinos',
                        'promociones',
                        'regiones_destacadas',
                        'tags_populares',
                        'recomendaciones',
                    ],
                    'message',
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertCount(1, $response->json('data.top_destinos'));
        $this->assertCount(1, $response->json('data.promociones'));
        $this->assertCount(1, $response->json('data.regiones_destacadas'));
        $this->assertCount(2, $response->json('data.tags_populares')); // 1 creado + 1 del seeder
    }

    #[Test]
    public function it_returns_cached_data()
    {
        // Crear datos mínimos
        $user = User::factory()->create();
        $user->assignRole('provider');
        $region = Region::factory()->create();
        $destino = Destino::factory()->create([
            'user_id' => $user->id,
            'region_id' => $region->id,
            'status' => 'published',
        ]);

        // Primera petición
        $response1 = $this->getJson('/api/v1/public/home');
        $response1->assertStatus(200);

        // Segunda petición (debería usar caché)
        $response2 = $this->getJson('/api/v1/public/home');
        $response2->assertStatus(200);

        // Las respuestas deberían ser idénticas
        $this->assertEquals($response1->json(), $response2->json());
    }
} 