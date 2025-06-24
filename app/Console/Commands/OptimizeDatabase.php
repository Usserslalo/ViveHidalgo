<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OptimizeDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:optimize-database {--analyze : Analyze table statistics} {--optimize : Optimize tables} {--clean : Clean old data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize database performance and clean old data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting database optimization...');

        if ($this->option('analyze')) {
            $this->analyzeTables();
        }

        if ($this->option('optimize')) {
            $this->optimizeTables();
        }

        if ($this->option('clean')) {
            $this->cleanOldData();
        }

        if (!$this->option('analyze') && !$this->option('optimize') && !$this->option('clean')) {
            $this->analyzeTables();
            $this->optimizeTables();
            $this->cleanOldData();
        }

        $this->info('✅ Database optimization completed successfully!');
    }

    /**
     * Analyze table statistics
     */
    private function analyzeTables(): void
    {
        $this->info('📊 Analyzing table statistics...');

        $tables = $this->getTables();

        foreach ($tables as $table) {
            try {
                DB::statement("ANALYZE TABLE {$table}");
                $this->line("  ✓ Analyzed table: {$table}");
            } catch (\Exception $e) {
                $this->warn("  ⚠ Could not analyze table {$table}: " . $e->getMessage());
            }
        }
    }

    /**
     * Optimize tables
     */
    private function optimizeTables(): void
    {
        $this->info('🔧 Optimizing tables...');

        $tables = $this->getTables();

        foreach ($tables as $table) {
            try {
                DB::statement("OPTIMIZE TABLE {$table}");
                $this->line("  ✓ Optimized table: {$table}");
            } catch (\Exception $e) {
                $this->warn("  ⚠ Could not optimize table {$table}: " . $e->getMessage());
            }
        }
    }

    /**
     * Clean old data
     */
    private function cleanOldData(): void
    {
        $this->info('🧹 Cleaning old data...');

        // Clean old audit logs (older than 1 year)
        $deletedAuditLogs = DB::table('audit_logs')
            ->where('created_at', '<', now()->subYear())
            ->delete();
        
        if ($deletedAuditLogs > 0) {
            $this->line("  ✓ Deleted {$deletedAuditLogs} old audit logs");
        }

        // Clean old notifications (older than 6 months)
        $deletedNotifications = DB::table('notifications')
            ->where('created_at', '<', now()->subMonths(6))
            ->delete();
        
        if ($deletedNotifications > 0) {
            $this->line("  ✓ Deleted {$deletedNotifications} old notifications");
        }

        // Clean old failed jobs (older than 1 month)
        $deletedFailedJobs = DB::table('failed_jobs')
            ->where('failed_at', '<', now()->subMonth())
            ->delete();
        
        if ($deletedFailedJobs > 0) {
            $this->line("  ✓ Deleted {$deletedFailedJobs} old failed jobs");
        }

        // Clean old cache
        $this->call('cache:clear');
        $this->line("  ✓ Cleared application cache");

        // Clean old sessions (older than 1 month)
        $deletedSessions = DB::table('sessions')
            ->where('last_activity', '<', now()->subMonth()->timestamp)
            ->delete();
        
        if ($deletedSessions > 0) {
            $this->line("  ✓ Deleted {$deletedSessions} old sessions");
        }
    }

    /**
     * Get all tables in the database
     */
    private function getTables(): array
    {
        $tables = [];
        
        try {
            $results = DB::select('SHOW TABLES');
            foreach ($results as $result) {
                $tables[] = array_values((array) $result)[0];
            }
        } catch (\Exception $e) {
            $this->error('Could not retrieve tables: ' . $e->getMessage());
        }

        return $tables;
    }
} 