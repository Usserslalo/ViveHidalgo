<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Destino;
use App\Models\Review;
use App\Models\Region;
use App\Models\Categoria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $destino;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear roles si no existen
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        
        $this->user = User::factory()->create();
        $this->user->assignRole('tourist');
        
        $region = Region::factory()->create();
        
        $this->destino = Destino::factory()->create([
            'region_id' => $region->id,
        ]);
    }

    /** @test */
    public function user_can_create_review_for_favorite_destino()
    {
        // Agregar destino a favoritos
        $this->user->favoritos()->attach($this->destino->id);
        
        Sanctum::actingAs($this->user);

        $reviewData = [
            'rating' => 5,
            'comment' => 'Excelente destino turístico',
        ];

        $response = $this->postJson("/api/v1/user/reviews/{$this->destino->id}", $reviewData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'user_id',
                    'destino_id',
                    'rating',
                    'comment',
                    'is_approved',
                    'created_at',
                    'updated_at',
                    'user' => [
                        'id',
                        'name',
                        'email',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->user->id,
            'destino_id' => $this->destino->id,
            'rating' => 5,
            'comment' => 'Excelente destino turístico',
            'is_approved' => false,
        ]);
    }

    /** @test */
    public function user_cannot_create_review_for_non_favorite_destino()
    {
        Sanctum::actingAs($this->user);

        $reviewData = [
            'rating' => 5,
            'comment' => 'Excelente destino turístico',
        ];

        $response = $this->postJson("/api/v1/user/reviews/{$this->destino->id}", $reviewData);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'No puedes reseñar este destino. Asegúrate de tenerlo en favoritos y no haberlo reseñado antes.',
            ]);
    }

    /** @test */
    public function user_cannot_create_multiple_reviews_for_same_destino()
    {
        // Agregar destino a favoritos
        $this->user->favoritos()->attach($this->destino->id);
        
        // Crear primera reseña
        Review::create([
            'user_id' => $this->user->id,
            'destino_id' => $this->destino->id,
            'rating' => 4,
            'comment' => 'Primera reseña',
            'is_approved' => true,
        ]);
        
        Sanctum::actingAs($this->user);

        $reviewData = [
            'rating' => 5,
            'comment' => 'Segunda reseña',
        ];

        $response = $this->postJson("/api/v1/user/reviews/{$this->destino->id}", $reviewData);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'No puedes reseñar este destino. Asegúrate de tenerlo en favoritos y no haberlo reseñado antes.',
            ]);
    }

    /** @test */
    public function user_can_update_own_review()
    {
        // Agregar destino a favoritos
        $this->user->favoritos()->attach($this->destino->id);
        
        $review = Review::create([
            'user_id' => $this->user->id,
            'destino_id' => $this->destino->id,
            'rating' => 3,
            'comment' => 'Reseña original',
            'is_approved' => false,
        ]);
        
        Sanctum::actingAs($this->user);

        $updateData = [
            'rating' => 5,
            'comment' => 'Reseña actualizada',
        ];

        $response = $this->putJson("/api/v1/user/reviews/{$review->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Reseña actualizada exitosamente.',
            ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'comment' => 'Reseña actualizada',
        ]);
    }

    /** @test */
    public function user_cannot_update_other_user_review()
    {
        $otherUser = User::factory()->create();
        $otherUser->assignRole('tourist');
        
        $review = Review::create([
            'user_id' => $otherUser->id,
            'destino_id' => $this->destino->id,
            'rating' => 3,
            'comment' => 'Reseña de otro usuario',
            'is_approved' => false,
        ]);
        
        Sanctum::actingAs($this->user);

        $updateData = [
            'rating' => 5,
            'comment' => 'Intento de edición',
        ];

        $response = $this->putJson("/api/v1/user/reviews/{$review->id}", $updateData);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'No puedes editar esta reseña.',
            ]);
    }

    /** @test */
    public function user_can_delete_own_review()
    {
        $review = Review::create([
            'user_id' => $this->user->id,
            'destino_id' => $this->destino->id,
            'rating' => 3,
            'comment' => 'Reseña a eliminar',
            'is_approved' => false,
        ]);
        
        Sanctum::actingAs($this->user);

        $response = $this->deleteJson("/api/v1/user/reviews/{$review->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Reseña eliminada exitosamente.',
            ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }

    /** @test */
    public function user_cannot_delete_other_user_review()
    {
        $otherUser = User::factory()->create();
        $otherUser->assignRole('tourist');
        
        $review = Review::create([
            'user_id' => $otherUser->id,
            'destino_id' => $this->destino->id,
            'rating' => 3,
            'comment' => 'Reseña de otro usuario',
            'is_approved' => false,
        ]);
        
        Sanctum::actingAs($this->user);

        $response = $this->deleteJson("/api/v1/user/reviews/{$review->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'No puedes eliminar esta reseña.',
            ]);
    }

    /** @test */
    public function user_can_get_own_reviews()
    {
        // Crear algunas reseñas para el usuario
        Review::create([
            'user_id' => $this->user->id,
            'destino_id' => $this->destino->id,
            'rating' => 4,
            'comment' => 'Primera reseña',
            'is_approved' => true,
        ]);
        
        // Crear un segundo destino para la segunda reseña
        $secondDestino = Destino::factory()->create([
            'region_id' => $this->destino->region_id,
        ]);
        
        Review::create([
            'user_id' => $this->user->id,
            'destino_id' => $secondDestino->id,
            'rating' => 5,
            'comment' => 'Segunda reseña',
            'is_approved' => false,
        ]);
        
        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/v1/user/reviews");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'user_id',
                            'destino_id',
                            'rating',
                            'comment',
                            'is_approved',
                            'created_at',
                            'updated_at',
                            'destino' => [
                                'id',
                                'name',
                            ],
                        ],
                    ],
                    'current_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    /** @test */
    public function public_can_get_approved_reviews_for_destino()
    {
        // Crear reseñas aprobadas y no aprobadas
        Review::create([
            'user_id' => $this->user->id,
            'destino_id' => $this->destino->id,
            'rating' => 4,
            'comment' => 'Reseña aprobada',
            'is_approved' => true,
        ]);
        
        // Crear un segundo usuario para la segunda reseña
        $secondUser = User::factory()->create();
        $secondUser->assignRole('tourist');
        
        Review::create([
            'user_id' => $secondUser->id,
            'destino_id' => $this->destino->id,
            'rating' => 5,
            'comment' => 'Reseña no aprobada',
            'is_approved' => false,
        ]);

        $response = $this->getJson("/api/v1/public/destinos/{$this->destino->id}/reviews");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'user_id',
                            'destino_id',
                            'rating',
                            'comment',
                            'is_approved',
                            'created_at',
                            'updated_at',
                            'user' => [
                                'id',
                                'name',
                            ],
                        ],
                    ],
                    'current_page',
                    'per_page',
                    'total',
                ],
            ]);

        // Verificar que solo se devuelven reseñas aprobadas
        $responseData = $response->json('data.data');
        $this->assertCount(1, $responseData);
        $this->assertEquals('Reseña aprobada', $responseData[0]['comment']);
    }

    /** @test */
    public function review_validation_requires_rating_between_1_and_5()
    {
        // Agregar destino a favoritos
        $this->user->favoritos()->attach($this->destino->id);
        
        Sanctum::actingAs($this->user);

        // Rating muy bajo
        $response = $this->postJson("/api/v1/user/reviews/{$this->destino->id}", [
            'rating' => 0,
            'comment' => 'Test comment',
        ]);

        $response->assertStatus(422);

        // Rating muy alto
        $response = $this->postJson("/api/v1/user/reviews/{$this->destino->id}", [
            'rating' => 6,
            'comment' => 'Test comment',
        ]);

        $response->assertStatus(422);

        // Rating válido
        $response = $this->postJson("/api/v1/user/reviews/{$this->destino->id}", [
            'rating' => 5,
            'comment' => 'Test comment',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function review_comment_is_optional()
    {
        // Agregar destino a favoritos
        $this->user->favoritos()->attach($this->destino->id);
        
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/user/reviews/{$this->destino->id}", [
            'rating' => 4,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->user->id,
            'destino_id' => $this->destino->id,
            'rating' => 4,
            'comment' => null,
        ]);
    }

    /** @test */
    public function review_comment_has_max_length_validation()
    {
        // Agregar destino a favoritos
        $this->user->favoritos()->attach($this->destino->id);
        
        Sanctum::actingAs($this->user);

        $longComment = str_repeat('a', 1001);

        $response = $this->postJson("/api/v1/user/reviews/{$this->destino->id}", [
            'rating' => 4,
            'comment' => $longComment,
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function destino_average_rating_and_count_are_updated_when_review_is_approved()
    {
        // Agregar destino a favoritos
        $this->user->favoritos()->attach($this->destino->id);
        
        Sanctum::actingAs($this->user);

        // Crear reseña
        $response = $this->postJson("/api/v1/user/reviews/{$this->destino->id}", [
            'rating' => 4,
            'comment' => 'Test review',
        ]);

        $response->assertStatus(200);

        // Verificar que el destino no tiene estadísticas aún (reseña no aprobada)
        $this->destino->refresh();
        $this->assertEquals(0, $this->destino->average_rating);
        $this->assertEquals(0, $this->destino->reviews_count);

        // Aprobar la reseña
        $review = Review::where('user_id', $this->user->id)
            ->where('destino_id', $this->destino->id)
            ->first();
        
        $review->update(['is_approved' => true]);

        // Verificar que las estadísticas se actualizaron
        $this->destino->refresh();
        $this->assertEquals(4.0, $this->destino->average_rating);
        $this->assertEquals(1, $this->destino->reviews_count);
    }
} 