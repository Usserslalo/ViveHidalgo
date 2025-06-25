<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Notifications\SubscriptionRenewalReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendSubscriptionReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:send-reminders 
                            {--days=7 : Días antes de la renovación para enviar recordatorio}
                            {--dry-run : Ejecutar sin enviar notificaciones}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar recordatorios de renovación de suscripciones';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info("Buscando suscripciones que se renuevan en {$days} días...");

        // Buscar suscripciones que se renuevan en X días
        $subscriptions = Subscription::where('status', 'active')
            ->where('current_period_end', '<=', now()->addDays($days))
            ->where('current_period_end', '>', now())
            ->with('user')
            ->get();

        $this->info("Encontradas {$subscriptions->count()} suscripciones para recordatorio.");

        if ($subscriptions->isEmpty()) {
            $this->info('No hay suscripciones que requieran recordatorio.');
            return 0;
        }

        $sentCount = 0;
        $errorCount = 0;

        foreach ($subscriptions as $subscription) {
            try {
                $daysUntilRenewal = $subscription->current_period_end->diffInDays(now());
                
                $this->line("Procesando suscripción #{$subscription->id} - Usuario: {$subscription->user->name} - Renovación en {$daysUntilRenewal} días");

                if (!$dryRun) {
                    $subscription->user->notify(new SubscriptionRenewalReminder($subscription, $daysUntilRenewal));
                    $sentCount++;
                    
                    Log::info('Subscription renewal reminder sent', [
                        'subscription_id' => $subscription->id,
                        'user_id' => $subscription->user->id,
                        'days_until_renewal' => $daysUntilRenewal,
                    ]);
                } else {
                    $this->line("  [DRY RUN] Se enviaría recordatorio a {$subscription->user->email}");
                    $sentCount++;
                }

            } catch (\Exception $e) {
                $errorCount++;
                $this->error("Error enviando recordatorio para suscripción #{$subscription->id}: " . $e->getMessage());
                
                Log::error('Error sending subscription reminder', [
                    'subscription_id' => $subscription->id,
                    'user_id' => $subscription->user->id ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Resumen:");
        $this->info("- Recordatorios enviados: {$sentCount}");
        $this->info("- Errores: {$errorCount}");

        if ($dryRun) {
            $this->warn('Ejecutado en modo DRY RUN - No se enviaron notificaciones reales');
        }

        return 0;
    }
} 