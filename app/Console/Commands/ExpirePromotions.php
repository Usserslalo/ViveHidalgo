<?php

namespace App\Console\Commands;

use App\Models\Promocion;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ExpirePromotions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:expire-promotions {--dry-run : Ejecutar en modo simulación sin hacer cambios}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Desactiva todas las promociones cuyo periodo de vigencia ha expirado.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Iniciando verificación de promociones expiradas...');
        
        $now = Carbon::now();
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->warn('⚠️  Ejecutando en modo simulación (dry-run)');
        }

        try {
            // Buscar promociones expiradas
            $expiredPromotions = Promocion::where('is_active', true)
                ->whereNotNull('end_date')
                ->where('end_date', '<', $now)
                ->get();

            $count = $expiredPromotions->count();
            
            if ($count === 0) {
                $this->info('✅ No se encontraron promociones expiradas.');
                Log::info('Comando expire-promotions ejecutado: No se encontraron promociones expiradas');
                return 0;
            }

            $this->info("📋 Se encontraron {$count} promociones expiradas:");
            
            $processedCount = 0;
            foreach ($expiredPromotions as $promo) {
                $this->line("  - {$promo->titulo} (ID: {$promo->id}) - Expiró: {$promo->end_date->format('Y-m-d H:i:s')}");
                
                if (!$isDryRun) {
                    $promo->is_active = false;
                    $promo->save();
                    
                    // Log individual para cada promoción desactivada
                    Log::info("Promoción desactivada automáticamente", [
                        'promocion_id' => $promo->id,
                        'titulo' => $promo->titulo,
                        'fecha_expiracion' => $promo->end_date,
                        'fecha_desactivacion' => $now
                    ]);
                }
                
                $processedCount++;
            }

            if ($isDryRun) {
                $this->warn("⚠️  Modo simulación: {$processedCount} promociones serían desactivadas");
                Log::info("Comando expire-promotions ejecutado en modo dry-run: {$processedCount} promociones serían desactivadas");
            } else {
                $this->info("✅ {$processedCount} promociones han sido desactivadas exitosamente.");
                Log::info("Comando expire-promotions ejecutado: {$processedCount} promociones desactivadas");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error al procesar promociones expiradas: " . $e->getMessage());
            Log::error("Error en comando expire-promotions", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
