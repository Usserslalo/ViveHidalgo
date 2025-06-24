<?php

namespace App\Console\Commands;

use App\Services\AuditService;
use Illuminate\Console\Command;

class CleanAuditLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clean-audit-logs 
                            {--days=90 : Número de días a mantener}
                            {--dry-run : Ejecutar en modo simulación sin eliminar logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpiar logs de auditoría antiguos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $daysToKeep = (int) $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info("🧹 Iniciando limpieza de logs de auditoría...");
        $this->info("📅 Manteniendo logs de los últimos {$daysToKeep} días");

        if ($dryRun) {
            $this->warn("🔍 MODO SIMULACIÓN - No se eliminarán logs");
        }

        try {
            // Obtener estadísticas antes de la limpieza
            $totalLogs = \App\Models\AuditLog::count();
            $logsToDelete = \App\Models\AuditLog::where('created_at', '<', now()->subDays($daysToKeep))->count();

            $this->info("📊 Estadísticas actuales:");
            $this->info("   - Total de logs: {$totalLogs}");
            $this->info("   - Logs a eliminar: {$logsToDelete}");

            if ($logsToDelete === 0) {
                $this->info("✅ No hay logs antiguos para eliminar");
                return 0;
            }

            if ($dryRun) {
                $this->info("🔍 Simulación completada. Se eliminarían {$logsToDelete} logs.");
                return 0;
            }

            // Ejecutar limpieza
            $deletedCount = AuditService::cleanOldLogs($daysToKeep);

            $this->info("✅ Limpieza completada exitosamente");
            $this->info("🗑️  Se eliminaron {$deletedCount} logs antiguos");

            // Estadísticas después de la limpieza
            $remainingLogs = \App\Models\AuditLog::count();
            $this->info("📊 Logs restantes: {$remainingLogs}");

            // Log del evento
            \Log::info("Audit logs cleanup completed", [
                'deleted_count' => $deletedCount,
                'days_to_keep' => $daysToKeep,
                'remaining_logs' => $remainingLogs,
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error durante la limpieza: " . $e->getMessage());
            \Log::error("Audit logs cleanup failed", [
                'error' => $e->getMessage(),
                'days_to_keep' => $daysToKeep,
            ]);
            return 1;
        }
    }
} 