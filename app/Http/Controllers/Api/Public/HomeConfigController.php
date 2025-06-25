<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\BaseController;
use App\Models\HomeConfig;
use App\Models\Destino;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class HomeConfigController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/v1/public/home/config",
     *     operationId="getHomeConfig",
     *     tags={"Public Home"},
     *     summary="Obtener configuración de la portada",
     *     description="Retorna la configuración activa de la portada incluyendo hero, secciones destacadas y destinos TOP.",
     *     @OA\Response(
     *         response=200,
     *         description="Configuración recuperada exitosamente",
     *         @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="hero", type="object",
     *                      @OA\Property(property="background_image", type="string", nullable=true),
     *                      @OA\Property(property="title", type="string"),
     *                      @OA\Property(property="subtitle", type="string"),
     *                      @OA\Property(property="search_placeholder", type="string")
     *                  ),
     *                  @OA\Property(property="featured_sections", type="array",
     *                      @OA\Items(type="object",
     *                          @OA\Property(property="slug", type="string"),
     *                          @OA\Property(property="title", type="string"),
     *                          @OA\Property(property="subtitle", type="string"),
     *                          @OA\Property(property="image", type="string", nullable=true),
     *                          @OA\Property(property="destinations_count", type="integer"),
     *                          @OA\Property(property="destinations", type="array", @OA\Items(ref="#/components/schemas/Destino"))
     *                      )
     *                  ),
     *                  @OA\Property(property="top_destinations", type="array", @OA\Items(ref="#/components/schemas/Destino"))
     *              ),
     *              @OA\Property(property="message", type="string", example="Configuración de portada recuperada exitosamente.")
     *         )
     *     )
     * )
     */
    public function show(): JsonResponse
    {
        return $this->getCachedData('public_home_config', function () {
            return $this->performHomeConfigSearch();
        }, 3600); // Cache por 1 hora
    }

    /**
     * Obtener la configuración de la portada
     */
    private function performHomeConfigSearch(): JsonResponse
    {
        // Obtener configuración activa
        $config = HomeConfig::getActive();

        // Configuración por defecto si no hay configuración activa
        $hero = [
            'background_image' => $config?->hero_image_path ?? null,
            'title' => $config?->hero_title ?? 'Descubre Hidalgo',
            'subtitle' => $config?->hero_subtitle ?? 'Tierra de aventura y tradición',
            'search_placeholder' => $config?->search_placeholder ?? 'Busca destinos, actividades...',
        ];

        // Secciones destacadas
        $featuredSections = [];
        if ($config && $config->featured_sections) {
            $featuredSections = collect($config->getFeaturedSectionsFormatted())->map(function ($section) {
                // Obtener destinos para esta sección
                $destinos = $this->getDestinosForSection($section['slug']);
                
                return [
                    'slug' => $section['slug'],
                    'title' => $section['title'],
                    'subtitle' => $section['subtitle'],
                    'image' => $section['image'],
                    'destinations_count' => $destinos->count(),
                    'destinations' => $destinos->take(6)->map(function ($destino) {
                        return [
                            'id' => $destino->id,
                            'titulo' => $destino->name,
                            'slug' => $destino->slug,
                            'imagen_principal' => $destino->imagenes->first() ? $destino->imagenes->first()->url : null,
                            'rating' => $destino->average_rating,
                            'reviews_count' => $destino->reviews_count,
                            'region' => $destino->region ? $destino->region->name : null,
                        ];
                    }),
                ];
            })->toArray();
        }

        // Destinos TOP
        $topDestinations = Destino::where('status', 'published')
            ->where('is_top', true)
            ->with([
                'region:id,name',
                'imagenes' => function ($q) { $q->main(); },
                'caracteristicas' => function ($q) { $q->activas(); }
            ])
            ->orderByDesc('average_rating')
            ->limit(8)
            ->get()
            ->map(function ($destino) {
                return [
                    'id' => $destino->id,
                    'titulo' => $destino->name,
                    'slug' => $destino->slug,
                    'imagen_principal' => $destino->imagenes->first() ? $destino->imagenes->first()->url : null,
                    'rating' => $destino->average_rating,
                    'reviews_count' => $destino->reviews_count,
                    'region' => $destino->region ? $destino->region->name : null,
                    'caracteristicas' => $destino->caracteristicas->pluck('nombre'),
                ];
            });

        $results = [
            'hero' => $hero,
            'featured_sections' => $featuredSections,
            'top_destinations' => $topDestinations,
        ];

        return $this->successResponse($results, 'Configuración de portada recuperada exitosamente.');
    }

    /**
     * Obtener destinos para una sección específica
     */
    private function getDestinosForSection(string $sectionSlug)
    {
        $query = Destino::where('status', 'published')
            ->with([
                'region:id,name',
                'imagenes' => function ($q) { $q->main(); }
            ]);

        // Lógica para filtrar por sección
        switch ($sectionSlug) {
            case 'pueblos-magicos':
                $query->whereHas('categorias', function ($q) {
                    $q->where('name', 'like', '%Pueblo Mágico%');
                });
                break;
            case 'aventura':
                $query->whereHas('categorias', function ($q) {
                    $q->where('name', 'like', '%Aventura%');
                });
                break;
            case 'cultura':
                $query->whereHas('categorias', function ($q) {
                    $q->where('name', 'like', '%Cultura%');
                });
                break;
            case 'gastronomia':
                $query->whereHas('caracteristicas', function ($q) {
                    $q->where('nombre', 'like', '%Gastronomía%');
                });
                break;
            case 'naturaleza':
                $query->whereHas('caracteristicas', function ($q) {
                    $q->where('nombre', 'like', '%Naturaleza%');
                });
                break;
            default:
                // Si no hay coincidencia específica, retornar destinos destacados
                $query->where('is_featured', true);
                break;
        }

        return $query->orderByDesc('average_rating')->limit(6)->get();
    }
} 