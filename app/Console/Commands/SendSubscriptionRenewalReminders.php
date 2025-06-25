<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Notifications\SubscriptionRenewalReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendSubscriptionRenewalReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:send-renewal-reminders 
                            {--days=7 : Días antes de la expiración para enviar recordatorios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar recordatorios de renovación de suscripción a usuarios próximos a expirar';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        
        $this->info("Buscando suscripciones que expiran en {$days} días...");

        // Obtener suscripciones que expiran pronto
        $subscriptions = Subscription::query()
            ->with('user')
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('end_date', '<=', now()->addDays($days))
            ->where('end_date', '>', now())
            ->where('auto_renew', true)
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->info('No hay suscripciones próximas a expirar.');
            return 0;
        }

        $this->info("Encontradas {$subscriptions->count()} suscripciones próximas a expirar.");

        $sentCount = 0;
        $failedCount = 0;

        foreach ($subscriptions as $subscription) {
            try {
                // Verificar que no se haya enviado un recordatorio recientemente
                $lastReminder = $subscription->user->notifications()
                    ->where('type', SubscriptionRenewalReminder::class)
                    ->where('data->subscription_id', $subscription->id)
                    ->where('created_at', '>=', now()->subDays(1))
                    ->first();

                if ($lastReminder) {
                    $this->warn("Recordatorio ya enviado recientemente para suscripción ID: {$subscription->id}");
                    continue;
                }

                // Enviar notificación
                $subscription->user->notify(new SubscriptionRenewalReminder($subscription));

                $this->info("Recordatorio enviado a: {$subscription->user->email} (Suscripción ID: {$subscription->id})");
                $sentCount++;

            } catch (\Exception $e) {
                $this->error("Error enviando recordatorio a {$subscription->user->email}: {$e->getMessage()}");
                $failedCount++;
            }
        }

        $this->info("Proceso completado:");
        $this->info("- Recordatorios enviados: {$sentCount}");
        $this->info("- Errores: {$failedCount}");

        return 0;
    }
} 