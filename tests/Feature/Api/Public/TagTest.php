<?php

namespace Tests\Feature\Api\Public;

use App\Models\Destino;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class TagTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear roles y permisos
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    #[Test]
    public function it_can_list_public_tags()
    {
        // Crear tags activos
        $activeTag1 = Tag::factory()->create(['is_active' => true]);
        $activeTag2 = Tag::factory()->create(['is_active' => true]);
        $inactiveTag = Tag::factory()->create(['is_active' => false]);

        $response = $this->getJson('/api/v1/public/tags');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                            'color',
                            'description',
                            'is_active',
                            'destinos_count',
                        ]
                    ],
                    'message',
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertCount(2, $response->json('data')); // Solo tags activos
    }

    #[Test]
    public function it_can_get_tag_by_slug()
    {
        $user = User::factory()->create();
        $user->assignRole('provider');

        $tag = Tag::factory()->create(['is_active' => true]);
        $destino = Destino::factory()->create([
            'user_id' => $user->id,
            'status' => 'published',
        ]);
        $destino->tags()->attach($tag->id);

        $response = $this->getJson("/api/v1/public/tags/{$tag->slug}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'name',
                        'slug',
                        'color',
                        'description',
                        'is_active',
                        'destinos' => [
                            '*' => [
                                'id',
                                'name',
                                'slug',
                                'region',
                                'imagenes',
                            ]
                        ],
                    ],
                    'message',
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals($tag->id, $response->json('data.id'));
        $this->assertCount(1, $response->json('data.destinos'));
    }

    #[Test]
    public function it_returns_404_for_inactive_tag()
    {
        $inactiveTag = Tag::factory()->create(['is_active' => false]);

        $response = $this->getJson("/api/v1/public/tags/{$inactiveTag->slug}");

        $response->assertStatus(404);
    }

    #[Test]
    public function it_returns_404_for_nonexistent_tag()
    {
        $response = $this->getJson('/api/v1/public/tags/nonexistent-tag');

        $response->assertStatus(404);
    }
} 