<?php

namespace Tests\Feature\Api;

use App\Models\Promocion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ExpirePromotionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Log::spy();
    }

    #[Test]
    public function it_expires_promotions_with_past_ends_at()
    {
        $expired = Promocion::factory()->create([
            'is_active' => true,
            'end_date' => Carbon::now()->subDay(),
        ]);
        $active = Promocion::factory()->create([
            'is_active' => true,
            'end_date' => Carbon::now()->addDay(),
        ]);
        $noEnd = Promocion::factory()->create([
            'is_active' => true,
            'end_date' => Carbon::now()->addYears(100),
        ]);

        $this->artisan('app:expire-promotions')
            ->expectsOutput('🔍 Iniciando verificación de promociones expiradas...')
            ->expectsOutput('📋 Se encontraron 1 promociones expiradas:')
            ->expectsOutput("  - {$expired->titulo} (ID: {$expired->id}) - Expiró: " . $expired->end_date->format('Y-m-d H:i:s'))
            ->expectsOutput('✅ 1 promociones han sido desactivadas exitosamente.')
            ->assertExitCode(0);

        $this->assertFalse($expired->fresh()->is_active);
        $this->assertTrue($active->fresh()->is_active);
        $this->assertTrue($noEnd->fresh()->is_active);

        Log::shouldHaveReceived('info')->with('Comando expire-promotions ejecutado: 1 promociones desactivadas');
    }

    #[Test]
    public function it_does_nothing_if_no_expired_promotions()
    {
        Promocion::factory()->create([
            'is_active' => true,
            'end_date' => Carbon::now()->addDay(),
        ]);
        Promocion::factory()->create([
            'is_active' => true,
            'end_date' => Carbon::now()->addYears(100),
        ]);

        $this->artisan('app:expire-promotions')
            ->expectsOutput('🔍 Iniciando verificación de promociones expiradas...')
            ->expectsOutput('✅ No se encontraron promociones expiradas.')
            ->assertExitCode(0);

        Log::shouldHaveReceived('info')->with('Comando expire-promotions ejecutado: No se encontraron promociones expiradas');
    }

    #[Test]
    public function it_can_be_run_multiple_times_safely()
    {
        $expired = Promocion::factory()->create([
            'is_active' => true,
            'end_date' => Carbon::now()->subDay(),
        ]);

        $this->artisan('app:expire-promotions')
            ->expectsOutput('🔍 Iniciando verificación de promociones expiradas...')
            ->expectsOutput('📋 Se encontraron 1 promociones expiradas:')
            ->expectsOutput("  - {$expired->titulo} (ID: {$expired->id}) - Expiró: " . $expired->end_date->format('Y-m-d H:i:s'))
            ->expectsOutput('✅ 1 promociones han sido desactivadas exitosamente.')
            ->assertExitCode(0);

        $this->artisan('app:expire-promotions')
            ->expectsOutput('🔍 Iniciando verificación de promociones expiradas...')
            ->expectsOutput('✅ No se encontraron promociones expiradas.')
            ->assertExitCode(0);
    }

    #[Test]
    public function it_supports_dry_run_mode()
    {
        $expired = Promocion::factory()->create([
            'is_active' => true,
            'end_date' => Carbon::now()->subDay(),
        ]);

        $this->artisan('app:expire-promotions', ['--dry-run' => true])
            ->expectsOutput('🔍 Iniciando verificación de promociones expiradas...')
            ->expectsOutput('⚠️  Ejecutando en modo simulación (dry-run)')
            ->expectsOutput('📋 Se encontraron 1 promociones expiradas:')
            ->expectsOutput("  - {$expired->titulo} (ID: {$expired->id}) - Expiró: " . $expired->end_date->format('Y-m-d H:i:s'))
            ->expectsOutput('⚠️  Modo simulación: 1 promociones serían desactivadas')
            ->assertExitCode(0);

        // La promoción no debe haber sido desactivada en modo dry-run
        $this->assertTrue($expired->fresh()->is_active);

        Log::shouldHaveReceived('info')->with('Comando expire-promotions ejecutado en modo dry-run: 1 promociones serían desactivadas');
    }

    #[Test]
    public function it_logs_individual_promotion_deactivation()
    {
        $expired = Promocion::factory()->create([
            'is_active' => true,
            'end_date' => Carbon::now()->subDay(),
        ]);

        $this->artisan('app:expire-promotions')->assertExitCode(0);

        Log::shouldHaveReceived('info')->with('Promoción desactivada automáticamente', [
            'promocion_id' => $expired->id,
            'titulo' => $expired->titulo,
            'fecha_expiracion' => $expired->end_date,
            'fecha_desactivacion' => \Mockery::type(Carbon::class)
        ]);
    }

    #[Test]
    public function it_handles_promotions_without_end_date()
    {
        // Promoción sin fecha de fin (no debe expirar)
        $noEndDate = Promocion::factory()->create([
            'is_active' => true,
            'end_date' => null,
        ]);

        $this->artisan('app:expire-promotions')
            ->expectsOutput('🔍 Iniciando verificación de promociones expiradas...')
            ->expectsOutput('✅ No se encontraron promociones expiradas.')
            ->assertExitCode(0);

        $this->assertTrue($noEndDate->fresh()->is_active);
    }

    #[Test]
    public function it_handles_already_inactive_promotions()
    {
        $expiredButInactive = Promocion::factory()->create([
            'is_active' => false,
            'end_date' => Carbon::now()->subDay(),
        ]);

        $this->artisan('app:expire-promotions')
            ->expectsOutput('🔍 Iniciando verificación de promociones expiradas...')
            ->expectsOutput('✅ No se encontraron promociones expiradas.')
            ->assertExitCode(0);

        $this->assertFalse($expiredButInactive->fresh()->is_active);
    }
} 