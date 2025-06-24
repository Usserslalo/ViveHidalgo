<?php

namespace Tests\Feature\Api;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Destino;
use App\Models\Subscription;
use App\Models\Review;
use App\Models\Promocion;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear roles necesarios para los tests
        $this->createRoles();
    }

    private function createRoles(): void
    {
        // Crear roles si no existen
        if (!\Spatie\Permission\Models\Role::where('name', 'admin')->exists()) {
            \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        }
        
        if (!\Spatie\Permission\Models\Role::where('name', 'provider')->exists()) {
            \Spatie\Permission\Models\Role::create(['name' => 'provider']);
        }
        
        if (!\Spatie\Permission\Models\Role::where('name', 'user')->exists()) {
            \Spatie\Permission\Models\Role::create(['name' => 'user']);
        }
    }

    #[Test]
    public function admin_can_get_audit_logs()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Crear algunos logs de auditoría
        AuditLog::factory()->count(5)->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/audit/logs');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'logs',
                        'pagination'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertCount(5, $response->json('data.logs'));
    }

    #[Test]
    public function non_admin_cannot_access_audit_logs()
    {
        $user = User::factory()->create();
        $user->assignRole('provider');

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/audit/logs');

        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'Acceso denegado'
                ]);
    }

    #[Test]
    public function admin_can_filter_audit_logs_by_event_type()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Crear logs con diferentes tipos de eventos
        AuditLog::factory()->created()->count(3)->create();
        AuditLog::factory()->updated()->count(2)->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/audit/logs?event_type=created');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data.logs'));
    }

    #[Test]
    public function admin_can_filter_audit_logs_by_user()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();

        // Crear logs para diferentes usuarios
        AuditLog::factory()->count(3)->create(['user_id' => $user->id]);
        AuditLog::factory()->count(2)->create(['user_id' => $admin->id]);

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/v1/audit/logs?user_id={$user->id}");

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data.logs'));
    }

    #[Test]
    public function admin_can_filter_audit_logs_by_date_range()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Crear logs con diferentes fechas
        AuditLog::factory()->count(3)->create([
            'created_at' => now()->subDays(5)
        ]);
        AuditLog::factory()->count(2)->create([
            'created_at' => now()->subDays(15)
        ]);

        Sanctum::actingAs($admin);

        $startDate = now()->subDays(10)->format('Y-m-d');
        $endDate = now()->subDays(1)->format('Y-m-d');

        $response = $this->getJson("/api/v1/audit/logs?start_date={$startDate}&end_date={$endDate}");

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data.logs'));
    }

    #[Test]
    public function admin_can_get_audit_stats()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Crear logs con diferentes tipos de eventos
        AuditLog::factory()->created()->count(3)->create();
        AuditLog::factory()->updated()->count(2)->create();
        AuditLog::factory()->login()->count(1)->create();

        Sanctum::actingAs($admin);

        // Usar un filtro de días más amplio para incluir todos los logs
        $response = $this->getJson('/api/v1/audit/stats?days=365');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'total_logs',
                        'events_by_type',
                        'top_users',
                        'recent_activity'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        // Verificar que hay al menos 6 logs (los que creamos en este test)
        $this->assertGreaterThanOrEqual(6, $response->json('data.total_logs'));
    }

    #[Test]
    public function admin_can_get_specific_audit_log()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $log = AuditLog::factory()->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/v1/audit/logs/{$log->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'id',
                        'event_type',
                        'event_description',
                        'user_name',
                        'auditable_name',
                        'description',
                        'ip_address',
                        'user_agent',
                        'url',
                        'method',
                        'old_values',
                        'new_values',
                        'changes',
                        'metadata',
                        'created_at',
                        'updated_at'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals($log->id, $response->json('data.id'));
    }

    #[Test]
    public function admin_can_clean_old_audit_logs()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Crear logs antiguos
        AuditLog::factory()->count(5)->create([
            'created_at' => now()->subDays(100)
        ]);

        // Crear logs recientes
        AuditLog::factory()->count(3)->create([
            'created_at' => now()->subDays(30)
        ]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson('/api/v1/audit/logs/clean?days_to_keep=90');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'deleted_count'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals(5, $response->json('data.deleted_count'));

        // Verificar que solo quedan los logs recientes
        $this->assertEquals(3, AuditLog::count());
    }

    #[Test]
    public function audit_service_logs_created_event()
    {
        $user = User::factory()->create();
        $destino = Destino::factory()->create();

        Sanctum::actingAs($user);

        $log = AuditService::logCreated($destino);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'event_type' => AuditLog::EVENT_CREATED,
            'auditable_type' => Destino::class,
            'auditable_id' => $destino->id,
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function audit_service_logs_updated_event()
    {
        $user = User::factory()->create();
        $destino = Destino::factory()->create();

        Sanctum::actingAs($user);

        $oldValues = ['name' => 'Nombre Antiguo'];
        $newValues = ['name' => 'Nombre Nuevo'];

        $log = AuditService::logUpdated($destino, $oldValues, $newValues);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'event_type' => AuditLog::EVENT_UPDATED,
            'auditable_type' => Destino::class,
            'auditable_id' => $destino->id,
            'user_id' => $user->id,
        ]);

        $this->assertEquals($oldValues, $log->old_values);
        $this->assertEquals($newValues, $log->new_values);
    }

    #[Test]
    public function audit_service_logs_deleted_event()
    {
        $user = User::factory()->create();
        $destino = Destino::factory()->create();

        Sanctum::actingAs($user);

        $log = AuditService::logDeleted($destino);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'event_type' => AuditLog::EVENT_DELETED,
            'auditable_type' => Destino::class,
            'auditable_id' => $destino->id,
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function audit_service_logs_login_event()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $log = AuditService::logLogin($user);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'event_type' => AuditLog::EVENT_LOGIN,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function audit_service_logs_logout_event()
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $log = AuditService::logLogout($user);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'event_type' => AuditLog::EVENT_LOGOUT,
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function audit_service_logs_subscription_events()
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        // Test subscription created
        $logCreated = AuditService::logSubscriptionCreated($subscription);
        $this->assertDatabaseHas('audit_logs', [
            'id' => $logCreated->id,
            'event_type' => AuditLog::EVENT_SUBSCRIPTION_CREATED,
        ]);

        // Test subscription cancelled
        $logCancelled = AuditService::logSubscriptionCancelled($subscription);
        $this->assertDatabaseHas('audit_logs', [
            'id' => $logCancelled->id,
            'event_type' => AuditLog::EVENT_SUBSCRIPTION_CANCELLED,
        ]);
    }

    #[Test]
    public function audit_service_logs_review_events()
    {
        $user = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        // Test review approved
        $logApproved = AuditService::logReviewApproved($review);
        $this->assertDatabaseHas('audit_logs', [
            'id' => $logApproved->id,
            'event_type' => AuditLog::EVENT_REVIEW_APPROVED,
        ]);

        // Test review rejected
        $logRejected = AuditService::logReviewRejected($review);
        $this->assertDatabaseHas('audit_logs', [
            'id' => $logRejected->id,
            'event_type' => AuditLog::EVENT_REVIEW_REJECTED,
        ]);
    }

    #[Test]
    public function audit_service_logs_promotion_expired_event()
    {
        $user = User::factory()->create();
        $destino = Destino::factory()->create(['user_id' => $user->id]);
        $promotion = Promocion::factory()->create(['destino_id' => $destino->id]);

        Sanctum::actingAs($user);

        $log = AuditService::logPromotionExpired($promotion);

        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'event_type' => AuditLog::EVENT_PROMOTION_EXPIRED,
            'auditable_type' => Promocion::class,
            'auditable_id' => $promotion->id,
        ]);
    }

    #[Test]
    public function audit_log_model_has_correct_relationships()
    {
        $user = User::factory()->create();
        $destino = Destino::factory()->create();
        $log = AuditLog::factory()->create([
            'user_id' => $user->id,
            'auditable_type' => Destino::class,
            'auditable_id' => $destino->id,
        ]);

        $this->assertInstanceOf(User::class, $log->user);
        $this->assertInstanceOf(Destino::class, $log->auditable);
    }

    #[Test]
    public function audit_log_model_has_correct_scopes()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Crear logs con diferentes tipos
        AuditLog::factory()->created()->count(3)->create();
        AuditLog::factory()->updated()->count(2)->create();
        AuditLog::factory()->login()->count(1)->create();

        $this->assertEquals(3, AuditLog::eventType('created')->count());
        $this->assertEquals(2, AuditLog::eventType('updated')->count());
        $this->assertEquals(1, AuditLog::eventType('login')->count());
    }

    #[Test]
    public function audit_log_model_has_correct_accessors()
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $log = AuditLog::factory()->create([
            'user_id' => $user->id,
            'event_type' => 'created',
        ]);

        $this->assertEquals('John Doe', $log->user_name);
        $this->assertEquals('Creó', $log->event_description);
    }

    #[Test]
    public function audit_service_get_stats_returns_correct_data()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Crear logs con diferentes tipos de eventos
        AuditLog::factory()->created()->count(3)->create();
        AuditLog::factory()->updated()->count(2)->create();
        AuditLog::factory()->login()->count(1)->create();

        // Usar un filtro de días más amplio para incluir todos los logs
        $stats = AuditService::getStats(365);

        $this->assertArrayHasKey('total_logs', $stats);
        $this->assertArrayHasKey('events_by_type', $stats);
        $this->assertArrayHasKey('top_users', $stats);
        $this->assertArrayHasKey('recent_activity', $stats);

        // Verificar que hay al menos 6 logs (los que creamos en este test)
        $this->assertGreaterThanOrEqual(6, $stats['total_logs']);
        
        // Verificar que los eventos por tipo están presentes
        $this->assertArrayHasKey('created', $stats['events_by_type']);
        $this->assertArrayHasKey('updated', $stats['events_by_type']);
        $this->assertArrayHasKey('login', $stats['events_by_type']);
        
        // Verificar que los conteos son correctos
        $this->assertEquals(3, $stats['events_by_type']['created']);
        $this->assertEquals(2, $stats['events_by_type']['updated']);
        $this->assertEquals(1, $stats['events_by_type']['login']);
    }
} 