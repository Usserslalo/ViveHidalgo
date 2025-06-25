<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use App\Models\User;
use App\Models\Subscription;
use App\Notifications\SubscriptionRenewalReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

class SendSubscriptionRenewalRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    /** @test */
    public function command_sends_reminders_to_expiring_subscriptions()
    {
        $user = User::factory()->create();
        
        // Crear suscripción que expira en 5 días
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => Subscription::STATUS_ACTIVE,
            'end_date' => now()->addDays(5),
            'auto_renew' => true,
        ]);

        $this->artisan('subscriptions:send-renewal-reminders', ['--days' => 7])
            ->expectsOutput('Buscando suscripciones que expiran en 7 días...')
            ->expectsOutput('Encontradas 1 suscripciones próximas a expirar.')
            ->expectsOutput("Recordatorio enviado a: {$user->email} (Suscripción ID: {$subscription->id})")
            ->expectsOutput('Proceso completado:')
            ->expectsOutput('- Recordatorios enviados: 1')
            ->expectsOutput('- Errores: 0')
            ->assertExitCode(0);

        Notification::assertSentTo($user, SubscriptionRenewalReminder::class);
    }

    /** @test */
    public function command_does_not_send_reminders_to_non_auto_renew_subscriptions()
    {
        $user = User::factory()->create();
        
        // Crear suscripción que expira en 5 días pero sin auto renovación
        Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => Subscription::STATUS_ACTIVE,
            'end_date' => now()->addDays(5),
            'auto_renew' => false,
        ]);

        $this->artisan('subscriptions:send-renewal-reminders', ['--days' => 7])
            ->expectsOutput('Buscando suscripciones que expiran en 7 días...')
            ->expectsOutput('Encontradas 0 suscripciones próximas a expirar.')
            ->expectsOutput('No hay suscripciones próximas a expirar.')
            ->assertExitCode(0);

        Notification::assertNotSentTo($user, SubscriptionRenewalReminder::class);
    }

    /** @test */
    public function command_does_not_send_reminders_to_expired_subscriptions()
    {
        $user = User::factory()->create();
        
        // Crear suscripción que ya expiró
        Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => Subscription::STATUS_ACTIVE,
            'end_date' => now()->subDays(1),
            'auto_renew' => true,
        ]);

        $this->artisan('subscriptions:send-renewal-reminders', ['--days' => 7])
            ->expectsOutput('Buscando suscripciones que expiran en 7 días...')
            ->expectsOutput('Encontradas 0 suscripciones próximas a expirar.')
            ->expectsOutput('No hay suscripciones próximas a expirar.')
            ->assertExitCode(0);

        Notification::assertNotSentTo($user, SubscriptionRenewalReminder::class);
    }

    /** @test */
    public function command_does_not_send_duplicate_reminders()
    {
        $user = User::factory()->create();
        
        // Crear suscripción que expira en 5 días
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => Subscription::STATUS_ACTIVE,
            'end_date' => now()->addDays(5),
            'auto_renew' => true,
        ]);

        // Crear notificación reciente
        $user->notifications()->create([
            'id' => 'test-notification-id',
            'type' => SubscriptionRenewalReminder::class,
            'data' => [
                'subscription_id' => $subscription->id,
                'message' => 'Tu suscripción expira pronto',
            ],
            'created_at' => now()->subHours(12),
        ]);

        $this->artisan('subscriptions:send-renewal-reminders', ['--days' => 7])
            ->expectsOutput('Buscando suscripciones que expiran en 7 días...')
            ->expectsOutput('Encontradas 1 suscripciones próximas a expirar.')
            ->expectsOutput("Recordatorio ya enviado recientemente para suscripción ID: {$subscription->id}")
            ->expectsOutput('Proceso completado:')
            ->expectsOutput('- Recordatorios enviados: 0')
            ->expectsOutput('- Errores: 0')
            ->assertExitCode(0);

        // Verificar que no se envió una nueva notificación
        Notification::assertNotSentTo($user, SubscriptionRenewalReminder::class);
    }

    /** @test */
    public function command_handles_multiple_subscriptions()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        // Crear suscripciones que expiran en diferentes días
        Subscription::factory()->create([
            'user_id' => $user1->id,
            'status' => Subscription::STATUS_ACTIVE,
            'end_date' => now()->addDays(3),
            'auto_renew' => true,
        ]);

        Subscription::factory()->create([
            'user_id' => $user2->id,
            'status' => Subscription::STATUS_ACTIVE,
            'end_date' => now()->addDays(7),
            'auto_renew' => true,
        ]);

        $this->artisan('subscriptions:send-renewal-reminders', ['--days' => 7])
            ->expectsOutput('Buscando suscripciones que expiran en 7 días...')
            ->expectsOutput('Encontradas 2 suscripciones próximas a expirar.')
            ->expectsOutput("Recordatorio enviado a: {$user1->email}")
            ->expectsOutput("Recordatorio enviado a: {$user2->email}")
            ->expectsOutput('Proceso completado:')
            ->expectsOutput('- Recordatorios enviados: 2')
            ->expectsOutput('- Errores: 0')
            ->assertExitCode(0);

        Notification::assertSentTo($user1, SubscriptionRenewalReminder::class);
        Notification::assertSentTo($user2, SubscriptionRenewalReminder::class);
    }

    /** @test */
    public function command_uses_default_days_when_not_specified()
    {
        $user = User::factory()->create();
        
        // Crear suscripción que expira en 7 días (default)
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => Subscription::STATUS_ACTIVE,
            'end_date' => now()->addDays(7),
            'auto_renew' => true,
        ]);

        $this->artisan('subscriptions:send-renewal-reminders')
            ->expectsOutput('Buscando suscripciones que expiran en 7 días...')
            ->expectsOutput('Encontradas 1 suscripciones próximas a expirar.')
            ->assertExitCode(0);

        Notification::assertSentTo($user, SubscriptionRenewalReminder::class);
    }

    /** @test */
    public function command_handles_custom_days_parameter()
    {
        $user = User::factory()->create();
        
        // Crear suscripción que expira en 10 días
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => Subscription::STATUS_ACTIVE,
            'end_date' => now()->addDays(10),
            'auto_renew' => true,
        ]);

        $this->artisan('subscriptions:send-renewal-reminders', ['--days' => 10])
            ->expectsOutput('Buscando suscripciones que expiran en 10 días...')
            ->expectsOutput('Encontradas 1 suscripciones próximas a expirar.')
            ->assertExitCode(0);

        Notification::assertSentTo($user, SubscriptionRenewalReminder::class);
    }
} 