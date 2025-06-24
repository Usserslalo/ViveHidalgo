<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Review;
use App\Models\Destino;
use App\Notifications\ReviewApproved;
use App\Notifications\ReviewRejected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $destino;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->destino = Destino::factory()->create();
        
        Notification::fake();
    }

    #[Test]
    public function user_can_get_their_notifications()
    {
        // Crear una notificación para el usuario
        $this->user->notify(new ReviewApproved(
            Review::factory()->create([
                'user_id' => $this->user->id,
                'destino_id' => $this->destino->id,
            ])
        ));

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/user/notifications');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'notifications' => [
                        '*' => [
                            'id',
                            'type',
                            'data',
                            'read_at',
                            'created_at',
                        ]
                    ],
                    'pagination' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total',
                    ],
                    'unread_count',
                ]
            ]);

        $this->assertEquals(1, $response->json('data.unread_count'));
    }

    #[Test]
    public function user_can_get_notification_stats()
    {
        // Crear notificaciones leídas y no leídas
        $this->user->notify(new ReviewApproved(
            Review::factory()->create([
                'user_id' => $this->user->id,
                'destino_id' => $this->destino->id,
            ])
        ));

        $this->user->notify(new ReviewRejected(
            Review::factory()->create([
                'user_id' => $this->user->id,
                'destino_id' => $this->destino->id,
            ])
        ));

        // Marcar una como leída
        $this->user->notifications()->first()->markAsRead();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/user/notifications/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total',
                    'unread',
                    'read',
                    'recent',
                ]
            ]);

        $data = $response->json('data');
        $this->assertEquals(2, $data['total']);
        $this->assertEquals(1, $data['unread']);
        $this->assertEquals(1, $data['read']);
    }

    #[Test]
    public function user_can_mark_notification_as_read()
    {
        $notification = $this->user->notify(new ReviewApproved(
            Review::factory()->create([
                'user_id' => $this->user->id,
                'destino_id' => $this->destino->id,
            ])
        ));

        $notificationId = $this->user->notifications()->first()->id;

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/v1/user/notifications/{$notificationId}/read");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'notification',
                    'unread_count',
                ]
            ]);

        $this->assertEquals(0, $response->json('data.unread_count'));
        $this->assertNotNull($this->user->notifications()->first()->read_at);
    }

    #[Test]
    public function user_can_mark_all_notifications_as_read()
    {
        // Crear múltiples notificaciones
        $this->user->notify(new ReviewApproved(
            Review::factory()->create([
                'user_id' => $this->user->id,
                'destino_id' => $this->destino->id,
            ])
        ));

        $this->user->notify(new ReviewRejected(
            Review::factory()->create([
                'user_id' => $this->user->id,
                'destino_id' => $this->destino->id,
            ])
        ));

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson('/api/v1/user/notifications/read-all');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'unread_count',
                ]
            ]);

        $this->assertEquals(0, $response->json('data.unread_count'));
        $this->assertEquals(0, $this->user->unreadNotifications()->count());
    }

    #[Test]
    public function user_can_delete_notification()
    {
        $notification = $this->user->notify(new ReviewApproved(
            Review::factory()->create([
                'user_id' => $this->user->id,
                'destino_id' => $this->destino->id,
            ])
        ));

        $notificationId = $this->user->notifications()->first()->id;

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/v1/user/notifications/{$notificationId}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'unread_count',
                ]
            ]);

        $this->assertEquals(0, $this->user->notifications()->count());
    }

    #[Test]
    public function user_can_filter_notifications_by_unread_only()
    {
        // Crear notificaciones leídas y no leídas
        $this->user->notify(new ReviewApproved(
            Review::factory()->create([
                'user_id' => $this->user->id,
                'destino_id' => $this->destino->id,
            ])
        ));

        $this->user->notify(new ReviewRejected(
            Review::factory()->create([
                'user_id' => $this->user->id,
                'destino_id' => $this->destino->id,
            ])
        ));

        // Marcar una como leída
        $this->user->notifications()->first()->markAsRead();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/user/notifications?unread_only=true');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data.notifications')));
    }

    #[Test]
    public function user_cannot_access_other_users_notifications()
    {
        $otherUser = User::factory()->create();
        
        $otherUser->notify(new ReviewApproved(
            Review::factory()->create([
                'user_id' => $otherUser->id,
                'destino_id' => $this->destino->id,
            ])
        ));

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/user/notifications');

        $response->assertStatus(200);
        $this->assertEquals(0, count($response->json('data.notifications')));
    }

    #[Test]
    public function unauthenticated_user_cannot_access_notifications()
    {
        $response = $this->getJson('/api/v1/user/notifications');

        $response->assertStatus(401);
    }

    #[Test]
    public function notification_is_sent_when_review_is_approved()
    {
        $review = Review::factory()->create([
            'user_id' => $this->user->id,
            'destino_id' => $this->destino->id,
            'is_approved' => false,
        ]);

        // Aprobar la reseña
        $review->update(['is_approved' => true]);

        $this->user->refresh();
        $this->assertEquals(1, $this->user->notifications()->count());
        
        $notification = $this->user->notifications()->first();
        $this->assertEquals('App\Notifications\ReviewApproved', $notification->type);
    }

    #[Test]
    public function notification_is_sent_when_review_is_rejected()
    {
        $review = Review::factory()->create([
            'user_id' => $this->user->id,
            'destino_id' => $this->destino->id,
            'is_approved' => true,
        ]);

        // Rechazar la reseña
        $review->update(['is_approved' => false]);

        $this->user->refresh();
        $this->assertEquals(1, $this->user->notifications()->count());
        
        $notification = $this->user->notifications()->first();
        $this->assertEquals('App\Notifications\ReviewRejected', $notification->type);
    }
} 