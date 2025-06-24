<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CleanAuditLogsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    #[Test]
    public function command_cleans_old_audit_logs()
    {
        // Crear logs antiguos (más de 90 días)
        AuditLog::factory()->count(5)->create([
            'created_at' => now()->subDays(100)
        ]);

        // Crear logs recientes (menos de 90 días)
        AuditLog::factory()->count(3)->create([
            'created_at' => now()->subDays(30)
        ]);

        $this->assertEquals(8, AuditLog::count());

        $this->artisan('app:clean-audit-logs')
            ->expectsOutput('🧹 Iniciando limpieza de logs de auditoría...')
            ->expectsOutput('📅 Manteniendo logs de los últimos 90 días')
            ->expectsOutput('📊 Estadísticas actuales:')
            ->expectsOutput('   - Total de logs: 8')
            ->expectsOutput('   - Logs a eliminar: 5')
            ->expectsOutput('✅ Limpieza completada exitosamente')
            ->expectsOutput('🗑️  Se eliminaron 5 logs antiguos')
            ->expectsOutput('📊 Logs restantes: 3')
            ->assertExitCode(0);

        $this->assertEquals(3, AuditLog::count());
    }

    #[Test]
    public function command_respects_custom_days_parameter()
    {
        // Crear logs de diferentes edades
        AuditLog::factory()->count(3)->create([
            'created_at' => now()->subDays(50)
        ]);

        AuditLog::factory()->count(2)->create([
            'created_at' => now()->subDays(30)
        ]);

        $this->assertEquals(5, AuditLog::count());

        $this->artisan('app:clean-audit-logs', ['--days' => 40])
            ->expectsOutput('📅 Manteniendo logs de los últimos 40 días')
            ->expectsOutput('   - Logs a eliminar: 3')
            ->expectsOutput('🗑️  Se eliminaron 3 logs antiguos')
            ->assertExitCode(0);

        $this->assertEquals(2, AuditLog::count());
    }

    #[Test]
    public function dry_run_mode_does_not_delete_logs()
    {
        // Crear logs antiguos
        AuditLog::factory()->count(3)->create([
            'created_at' => now()->subDays(100)
        ]);

        $this->assertEquals(3, AuditLog::count());

        $this->artisan('app:clean-audit-logs', ['--dry-run' => true])
            ->expectsOutput('🔍 MODO SIMULACIÓN - No se eliminarán logs')
            ->expectsOutput('🔍 Simulación completada. Se eliminarían 3 logs.')
            ->assertExitCode(0);

        // Los logs no deben haberse eliminado
        $this->assertEquals(3, AuditLog::count());
    }

    #[Test]
    public function command_handles_no_old_logs()
    {
        // Crear solo logs recientes
        AuditLog::factory()->count(3)->create([
            'created_at' => now()->subDays(30)
        ]);

        $this->assertEquals(3, AuditLog::count());

        $this->artisan('app:clean-audit-logs')
            ->expectsOutput('✅ No hay logs antiguos para eliminar')
            ->assertExitCode(0);

        // Los logs no deben haberse eliminado
        $this->assertEquals(3, AuditLog::count());
    }

    #[Test]
    public function command_handles_empty_database()
    {
        $this->assertEquals(0, AuditLog::count());

        $this->artisan('app:clean-audit-logs')
            ->expectsOutput('✅ No hay logs antiguos para eliminar')
            ->assertExitCode(0);

        $this->assertEquals(0, AuditLog::count());
    }

    #[Test]
    public function command_logs_activity()
    {
        // Crear logs antiguos
        AuditLog::factory()->count(2)->create([
            'created_at' => now()->subDays(100)
        ]);

        $this->artisan('app:clean-audit-logs')
            ->assertExitCode(0);

        // Verificar que se registró el evento en los logs
        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'created',
            'auditable_type' => 'App\Models\AuditLog',
        ]);
    }
} 