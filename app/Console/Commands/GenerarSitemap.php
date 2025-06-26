<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Destino;
use App\Models\Region;
use App\Models\Categoria;
use App\Models\PromocionDestacada;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class GenerarSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generar:sitemap {--force : Forzar regeneración del sitemap}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera un sitemap XML dinámico con todos los destinos, regiones y categorías';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generando sitemap XML...');

        $startTime = microtime(true);

        try {
            $sitemap = $this->generarSitemapXML();
            
            // Guardar el sitemap en public/sitemap.xml
            $sitemapPath = public_path('sitemap.xml');
            File::put($sitemapPath, $sitemap);

            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);

            $this->info("✅ Sitemap generado exitosamente en: {$sitemapPath}");
            $this->info("⏱️  Tiempo de ejecución: {$executionTime} segundos");
            
            // Mostrar estadísticas
            $this->mostrarEstadisticas();

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Error al generar sitemap: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Generar el contenido XML del sitemap
     */
    private function generarSitemapXML(): string
    {
        $baseUrl = config('app.url');
        $now = Carbon::now()->toISOString();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Página principal
        $xml .= $this->generarUrlXML($baseUrl, '1.0', 'daily', $now);

        // Destinos publicados
        $destinos = Destino::published()->get();
        foreach ($destinos as $destino) {
            $url = $baseUrl . '/destinos/' . $destino->slug;
            $lastmod = $destino->updated_at->toISOString();
            $priority = $destino->is_top ? '0.9' : '0.7';
            $changefreq = $destino->is_top ? 'weekly' : 'monthly';
            
            $xml .= $this->generarUrlXML($url, $priority, $changefreq, $lastmod);
        }

        // Regiones
        $regiones = Region::all();
        foreach ($regiones as $region) {
            $url = $baseUrl . '/regiones/' . $region->slug;
            $lastmod = $region->updated_at->toISOString();
            
            $xml .= $this->generarUrlXML($url, '0.8', 'weekly', $lastmod);
        }

        // Categorías
        $categorias = Categoria::all();
        foreach ($categorias as $categoria) {
            $url = $baseUrl . '/categorias/' . $categoria->slug;
            $lastmod = $categoria->updated_at->toISOString();
            
            $xml .= $this->generarUrlXML($url, '0.8', 'weekly', $lastmod);
        }

        // Promociones destacadas vigentes
        $promociones = PromocionDestacada::vigentes()->get();
        foreach ($promociones as $promocion) {
            $url = $baseUrl . '/promociones/' . $promocion->id;
            $lastmod = $promocion->updated_at->toISOString();
            
            $xml .= $this->generarUrlXML($url, '0.6', 'daily', $lastmod);
        }

        // Páginas estáticas importantes
        $paginasEstaticas = [
            '/about' => ['priority' => '0.5', 'changefreq' => 'monthly'],
            '/contact' => ['priority' => '0.5', 'changefreq' => 'monthly'],
            '/privacy' => ['priority' => '0.3', 'changefreq' => 'yearly'],
            '/terms' => ['priority' => '0.3', 'changefreq' => 'yearly'],
        ];

        foreach ($paginasEstaticas as $ruta => $config) {
            $url = $baseUrl . $ruta;
            $xml .= $this->generarUrlXML($url, $config['priority'], $config['changefreq'], $now);
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Generar una entrada de URL para el sitemap
     */
    private function generarUrlXML(string $url, string $priority, string $changefreq, string $lastmod): string
    {
        return "  <url>\n" .
               "    <loc>{$url}</loc>\n" .
               "    <lastmod>{$lastmod}</lastmod>\n" .
               "    <changefreq>{$changefreq}</changefreq>\n" .
               "    <priority>{$priority}</priority>\n" .
               "  </url>\n";
    }

    /**
     * Mostrar estadísticas del sitemap generado
     */
    private function mostrarEstadisticas(): void
    {
        $destinosCount = Destino::published()->count();
        $regionesCount = Region::count();
        $categoriasCount = Categoria::count();
        $promocionesCount = PromocionDestacada::vigentes()->count();
        $topDestinosCount = Destino::published()->where('is_top', true)->count();

        $this->newLine();
        $this->info('📊 Estadísticas del sitemap:');
        $this->line("   • Destinos publicados: {$destinosCount}");
        $this->line("   • Destinos TOP: {$topDestinosCount}");
        $this->line("   • Regiones: {$regionesCount}");
        $this->line("   • Categorías: {$categoriasCount}");
        $this->line("   • Promociones vigentes: {$promocionesCount}");
        
        $totalUrls = 1 + $destinosCount + $regionesCount + $categoriasCount + $promocionesCount + 4; // +4 páginas estáticas
        $this->line("   • Total de URLs: {$totalUrls}");
    }
}
