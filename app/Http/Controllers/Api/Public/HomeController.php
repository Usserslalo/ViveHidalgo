<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\BaseController;
use App\Models\Destino;
use App\Models\Promocion;
use App\Models\Region;
use App\Models\Tag;
use App\Models\HomeConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * @OA\Tag(
 *     name="Home",
 *     description="Endpoints para la página de inicio pública"
 * )
 */
class HomeController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/v1/home/hero",
     *     operationId="getHomeHero",
     *     tags={"Public Home"},
     *     summary="Obtener datos del hero section",
     *     description="Retorna información para el hero section incluyendo imagen de fondo, título, subtítulo, placeholder de búsqueda y destinos destacados.",
     *     @OA\Response(
     *         response=200,
     *         description="Datos del hero recuperados exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="hero", type="object",
     *                     @OA\Property(property="background_image", type="string", example="https://..."),
     *                     @OA\Property(property="title", type="string", example="Descubre Hidalgo"),
     *                     @OA\Property(property="subtitle", type="string", example="Tierra de aventura y tradición"),
     *                     @OA\Property(property="search_placeholder", type="string", example="Busca destinos, actividades...")
     *                 ),
     *                 @OA\Property(property="featured_destinations", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="slug", type="string"),
     *                     @OA\Property(property="imagen_principal", type="string"),
     *                     @OA\Property(property="rating", type="number", format="float"),
     *                     @OA\Property(property="reviews_count", type="integer"),
     *                     @OA\Property(property="favorite_count", type="integer"),
     *                     @OA\Property(property="price_range", type="string"),
     *                     @OA\Property(property="caracteristicas", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="region", type="string"),
     *                     @OA\Property(property="distance_km", type="number", format="float")
     *                 ))
     *             ),
     *             @OA\Property(property="message", type="string", example="Datos del hero recuperados exitosamente.")
     *         )
     *     )
     * )
     */
    public function hero(): \Illuminate\Http\JsonResponse
    {
        $data = Cache::remember('home_hero_data', 300, function () {
            // Obtener configuración del hero
            $homeConfig = HomeConfig::getActive();
            
            $heroData = [
                'background_image' => $homeConfig?->heroImagen?->url ?? 'https://via.placeholder.com/1920x1080/4F46E5/FFFFFF?text=Descubre+Hidalgo',
                'title' => $homeConfig?->hero_title ?? 'Descubre Hidalgo',
                'subtitle' => $homeConfig?->hero_subtitle ?? 'Tierra de aventura y tradición',
                'search_placeholder' => $homeConfig?->search_placeholder ?? 'Busca destinos, actividades...'
            ];

            // Obtener destinos destacados
            $featuredDestinations = Destino::with(['region', 'caracteristicas' => function ($query) {
                    $query->activas();
                }, 'imagenPrincipal'])
                ->where('status', 'published')
                ->where('is_featured', true)
                ->orderBy('created_at', 'desc')
                ->limit(6)
                ->get()
                ->map(function ($destino) {
                    return [
                        'id' => $destino->id,
                        'name' => $destino->name,
                        'slug' => $destino->slug,
                        'imagen_principal' => $destino->imagenPrincipal?->url ?? 'https://via.placeholder.com/400x300/6B7280/FFFFFF?text=' . urlencode($destino->name),
                        'rating' => $destino->average_rating ?? 4.5,
                        'reviews_count' => $destino->reviews_count ?? 0,
                        'favorite_count' => $destino->favorite_count ?? 0,
                        'price_range' => $destino->price_range ?? 'moderado',
                        'caracteristicas' => $destino->caracteristicas->take(3)->pluck('nombre')->toArray(),
                        'region' => $destino->region?->name ?? 'Hidalgo',
                        'distance_km' => $destino->distance_km ?? 15.2
                    ];
                });

            return [
                'hero' => $heroData,
                'featured_destinations' => $featuredDestinations
            ];
        });

        return $this->successResponse($data, 'Datos del hero recuperados exitosamente.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/home/sections",
     *     operationId="getHomeSections",
     *     tags={"Public Home"},
     *     summary="Obtener secciones destacadas",
     *     description="Retorna las secciones destacadas con información visual y conteo de destinos.",
     *     @OA\Response(
     *         response=200,
     *         description="Secciones recuperadas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="slug", type="string", example="pueblos-magicos"),
     *                 @OA\Property(property="title", type="string", example="Pueblos Mágicos"),
     *                 @OA\Property(property="subtitle", type="string", example="Descubre la magia de nuestros pueblos"),
     *                 @OA\Property(property="image", type="string", example="https://..."),
     *                 @OA\Property(property="destinations_count", type="integer", example=8),
     *                 @OA\Property(property="destinations", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="slug", type="string"),
     *                     @OA\Property(property="imagen_principal", type="string"),
     *                     @OA\Property(property="rating", type="number", format="float"),
     *                     @OA\Property(property="reviews_count", type="integer"),
     *                     @OA\Property(property="favorite_count", type="integer"),
     *                     @OA\Property(property="price_range", type="string"),
     *                     @OA\Property(property="caracteristicas", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="region", type="string"),
     *                     @OA\Property(property="distance_km", type="number", format="float")
     *                 ))
     *             )),
     *             @OA\Property(property="message", type="string", example="Secciones recuperadas exitosamente.")
     *         )
     *     )
     * )
     */
    public function sections(): \Illuminate\Http\JsonResponse
    {
        $data = Cache::remember('home_sections_data', 600, function () {
            $sections = [
                [
                    'slug' => 'pueblos-magicos',
                    'title' => 'Pueblos Mágicos',
                    'subtitle' => 'Descubre la magia de nuestros pueblos',
                    'image' => 'https://via.placeholder.com/800x600/8B5CF6/FFFFFF?text=Pueblos+Mágicos',
                    'destinations_count' => 0,
                    'destinations' => []
                ],
                [
                    'slug' => 'aventura',
                    'title' => 'Aventura',
                    'subtitle' => 'Experiencias llenas de adrenalina',
                    'image' => 'https://via.placeholder.com/800x600/10B981/FFFFFF?text=Aventura',
                    'destinations_count' => 0,
                    'destinations' => []
                ],
                [
                    'slug' => 'cultura',
                    'title' => 'Cultura',
                    'subtitle' => 'Historia y tradiciones vivas',
                    'image' => 'https://via.placeholder.com/800x600/F59E0B/FFFFFF?text=Cultura',
                    'destinations_count' => 0,
                    'destinations' => []
                ],
                [
                    'slug' => 'gastronomia',
                    'title' => 'Gastronomía',
                    'subtitle' => 'Sabores únicos de Hidalgo',
                    'image' => 'https://via.placeholder.com/800x600/EF4444/FFFFFF?text=Gastronomía',
                    'destinations_count' => 0,
                    'destinations' => []
                ],
                [
                    'slug' => 'naturaleza',
                    'title' => 'Naturaleza',
                    'subtitle' => 'Paisajes que te dejarán sin aliento',
                    'image' => 'https://via.placeholder.com/800x600/059669/FFFFFF?text=Naturaleza',
                    'destinations_count' => 0,
                    'destinations' => []
                ]
            ];

            // Poblar cada sección con destinos reales
            foreach ($sections as &$section) {
                $destinos = $this->getDestinosForSection($section['slug']);
                $section['destinations_count'] = $destinos->count();
                $section['destinations'] = $destinos->take(4)->map(function ($destino) {
                    return [
                        'id' => $destino->id,
                        'name' => $destino->name,
                        'slug' => $destino->slug,
                        'imagen_principal' => $destino->imagenPrincipal?->url ?? 'https://via.placeholder.com/400x300/6B7280/FFFFFF?text=' . urlencode($destino->name),
                        'rating' => $destino->average_rating ?? 4.5,
                        'reviews_count' => $destino->reviews_count ?? 0,
                        'favorite_count' => $destino->favorite_count ?? 0,
                        'price_range' => $destino->price_range ?? 'moderado',
                        'caracteristicas' => $destino->caracteristicas->take(3)->pluck('nombre')->toArray(),
                        'region' => $destino->region?->name ?? 'Hidalgo',
                        'distance_km' => $destino->distance_km ?? 15.2
                    ];
                })->toArray();
            }

            return $sections;
        });

        return $this->successResponse($data, 'Secciones recuperadas exitosamente.');
    }

    /**
     * Obtener destinos para una sección específica
     */
    private function getDestinosForSection(string $sectionSlug)
    {
        $query = Destino::with(['region', 'caracteristicas' => function ($query) {
                $query->activas();
            }, 'imagenPrincipal'])
            ->where('status', 'published');

        switch ($sectionSlug) {
            case 'pueblos-magicos':
                $query->whereHas('categorias', function ($q) {
                    $q->where('name', 'like', '%Pueblo Mágico%');
                });
                break;
            case 'aventura':
                $query->whereHas('caracteristicas', function ($q) {
                    $q->whereIn('nombre', ['Aventura', 'Deportes extremos', 'Senderismo', 'Escalada']);
                });
                break;
            case 'cultura':
                $query->whereHas('caracteristicas', function ($q) {
                    $q->whereIn('nombre', ['Historia', 'Arquitectura', 'Museos', 'Iglesias']);
                });
                break;
            case 'gastronomia':
                $query->whereHas('caracteristicas', function ($q) {
                    $q->whereIn('nombre', ['Gastronomía', 'Restaurantes', 'Cafeterías', 'Mercados']);
                });
                break;
            case 'naturaleza':
                $query->whereHas('caracteristicas', function ($q) {
                    $q->whereIn('nombre', ['Naturaleza', 'Parques', 'Cascadas', 'Montañas']);
                });
                break;
        }

        return $query->orderBy('average_rating', 'desc')
                    ->orderBy('reviews_count', 'desc')
                    ->limit(8)
                    ->get();
    }

    /**
     * @OA\Get(
     *     path="/api/v1/public/home",
     *     summary="Datos para la página de inicio",
     *     description="Devuelve destinos top, promociones activas, regiones destacadas, tags populares y recomendaciones.",
     *     tags={"Home"},
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="top_destinos", type="array", @OA\Items(ref="#/components/schemas/Destino")),
     *                 @OA\Property(property="promociones", type="array", @OA\Items(ref="#/components/schemas/Promocion")),
     *                 @OA\Property(property="regiones_destacadas", type="array", @OA\Items(ref="#/components/schemas/Region")),
     *                 @OA\Property(property="tags_populares", type="array", @OA\Items(ref="#/components/schemas/Tag")),
     *                 @OA\Property(property="recomendaciones", type="array", @OA\Items(ref="#/components/schemas/Destino"))
     *             ),
     *             @OA\Property(property="message", type="string", example="Datos de home recuperados con éxito.")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $data = Cache::remember('public_home', 60, function () {
            return [
                'top_destinos' => Destino::with(['region', 'imagenes' => function ($q) { $q->main(); }])
                    ->where('status', 'published')
                    ->where('is_top', true)
                    ->orderByDesc('average_rating')
                    ->take(8)
                    ->get(),
                'promociones' => Promocion::with(['destino', 'imagenes' => function ($q) { $q->main(); }])
                    ->where('is_active', true)
                    ->orderByDesc('start_date')
                    ->take(6)
                    ->get(),
                'regiones_destacadas' => Region::with(['imagenes' => function ($q) { $q->main(); }])
                    ->orderByDesc('id')
                    ->take(6)
                    ->get(),
                'tags_populares' => Tag::withCount('destinos')
                    ->orderByDesc('destinos_count')
                    ->active()
                    ->take(8)
                    ->get(),
                'recomendaciones' => Destino::with(['region', 'imagenes' => function ($q) { $q->main(); }])
                    ->where('status', 'published')
                    ->orderByDesc('average_rating')
                    ->take(8)
                    ->get(),
            ];
        });

        return $this->successResponse($data, 'Datos de home recuperados con éxito.');
    }
} 