<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:expire-subscriptions {--dry-run : Ejecutar en modo simulación sin hacer cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expirar suscripciones que han pasado su fecha de fin';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('🔍 Ejecutando en modo simulación (dry-run)...');
        } else {
            $this->info('🚀 Iniciando proceso de expiración de suscripciones...');
        }

        try {
            // Obtener suscripciones activas que han expirado
            $expiredSubscriptions = Subscription::where('status', Subscription::STATUS_ACTIVE)
                ->where('end_date', '<', now())
                ->get();

            $this->info("📊 Se encontraron {$expiredSubscriptions->count()} suscripciones expiradas.");

            if ($expiredSubscriptions->isEmpty()) {
                $this->info('✅ No hay suscripciones que expirar.');
                return 0;
            }

            $expiredCount = 0;
            $errorCount = 0;

            foreach ($expiredSubscriptions as $subscription) {
                try {
                    $this->line("📋 Procesando suscripción ID: {$subscription->id} - Usuario: {$subscription->user->name}");

                    if (!$isDryRun) {
                        $subscription->markAsExpired();
                        
                        // Log de la acción
                        Log::info('Suscripción expirada automáticamente', [
                            'subscription_id' => $subscription->id,
                            'user_id' => $subscription->user_id,
                            'plan_type' => $subscription->plan_type,
                            'expired_at' => now(),
                        ]);
                    }

                    $expiredCount++;
                    $this->info("✅ Suscripción ID {$subscription->id} marcada como expirada.");

                } catch (\Exception $e) {
                    $errorCount++;
                    $this->error("❌ Error al procesar suscripción ID {$subscription->id}: {$e->getMessage()}");
                    
                    if (!$isDryRun) {
                        Log::error('Error al expirar suscripción', [
                            'subscription_id' => $subscription->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            // Resumen final
            $this->newLine();
            $this->info('📈 RESUMEN DEL PROCESO:');
            $this->info("   • Suscripciones encontradas: {$expiredSubscriptions->count()}");
            $this->info("   • Suscripciones procesadas: {$expiredCount}");
            $this->info("   • Errores: {$errorCount}");

            if ($isDryRun) {
                $this->warn('⚠️  Este fue un modo simulación. No se realizaron cambios reales.');
            } else {
                $this->info('🎉 Proceso completado exitosamente.');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error general en el proceso: {$e->getMessage()}");
            
            if (!$isDryRun) {
                Log::error('Error general en comando expire-subscriptions', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            return 1;
        }
    }
} 