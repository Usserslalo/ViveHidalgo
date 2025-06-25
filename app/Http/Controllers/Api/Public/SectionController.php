<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\BaseController;
use App\Models\Destino;
use App\Models\Categoria;
use App\Models\Caracteristica;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SectionController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/v1/public/sections/{section_slug}",
     *     operationId="getSectionDestinations",
     *     tags={"Public Sections"},
     *     summary="Obtener destinos por sección visual",
     *     description="Retorna destinos agrupados por secciones visuales como Pueblos Mágicos, Aventura, Cultura, etc.",
     *     @OA\Parameter(
     *         name="section_slug",
     *         in="path",
     *         required=true,
     *         description="Slug de la sección (pueblos-magicos, aventura, cultura, gastronomia, naturaleza)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Número máximo de destinos (default: 12)",
     *         @OA\Schema(type="integer", default=12)
     *     ),
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         required=false,
     *         description="Número de destinos a omitir (paginación)",
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         required=false,
     *         description="Orden de resultados",
     *         @OA\Schema(type="string", enum={"rating", "popularidad", "nuevos", "distancia"}, default="rating")
     *     ),
     *     @OA\Parameter(
     *         name="latitude",
     *         in="query",
     *         required=false,
     *         description="Latitud para ordenamiento por distancia",
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="longitude",
     *         in="query",
     *         required=false,
     *         description="Longitud para ordenamiento por distancia",
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sección recuperada exitosamente",
     *         @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="section", type="object",
     *                      @OA\Property(property="slug", type="string"),
     *                      @OA\Property(property="title", type="string"),
     *                      @OA\Property(property="subtitle", type="string"),
     *                      @OA\Property(property="description", type="string"),
     *                      @OA\Property(property="total_destinations", type="integer"),
     *                      @OA\Property(property="image", type="string", nullable=true)
     *                  ),
     *                  @OA\Property(property="destinations", type="array", @OA\Items(ref="#/components/schemas/Destino")),
     *                  @OA\Property(property="pagination", type="object",
     *                      @OA\Property(property="current_page", type="integer"),
     *                      @OA\Property(property="per_page", type="integer"),
     *                      @OA\Property(property="total", type="integer"),
     *                      @OA\Property(property="has_more", type="boolean")
     *                  )
     *              ),
     *              @OA\Property(property="message", type="string", example="Sección recuperada exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Sección no encontrada",
     *         @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="Sección no encontrada.")
     *         )
     *     )
     * )
     */
    public function show(Request $request, string $sectionSlug): JsonResponse
    {
        // Validación del request
        $request->validate([
            'limit' => 'sometimes|integer|min:1|max:50',
            'offset' => 'sometimes|integer|min:0',
            'sort_by' => 'sometimes|in:rating,popularidad,nuevos,distancia',
            'latitude' => 'sometimes|numeric|between:-90,90',
            'longitude' => 'sometimes|numeric|between:-180,180',
        ]);

        $limit = $request->input('limit', 12);
        $offset = $request->input('offset', 0);
        $sortBy = $request->input('sort_by', 'rating');

        // Cache key único para esta sección
        $cacheKey = "section_{$sectionSlug}_{$limit}_{$offset}_{$sortBy}";
        
        return $this->getCachedData($cacheKey, function () use ($sectionSlug, $limit, $offset, $sortBy, $request) {
            return $this->performSectionSearch($sectionSlug, $limit, $offset, $sortBy, $request);
        }, 300); // Cache por 5 minutos
    }

    /**
     * @OA\Get(
     *     path="/api/v1/public/sections",
     *     operationId="getAllSections",
     *     tags={"Public Sections"},
     *     summary="Obtener todas las secciones disponibles",
     *     description="Retorna todas las secciones visuales disponibles con información básica.",
     *     @OA\Response(
     *         response=200,
     *         description="Secciones recuperadas exitosamente",
     *         @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="array",
     *                  @OA\Items(type="object",
     *                      @OA\Property(property="slug", type="string"),
     *                      @OA\Property(property="title", type="string"),
     *                      @OA\Property(property="subtitle", type="string"),
     *                      @OA\Property(property="description", type="string"),
     *                      @OA\Property(property="total_destinations", type="integer"),
     *                      @OA\Property(property="image", type="string", nullable=true)
     *                  )
     *              ),
     *              @OA\Property(property="message", type="string", example="Secciones recuperadas exitosamente.")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        return $this->getCachedData('public_sections', function () {
            return $this->performSectionsList();
        }, 600); // Cache por 10 minutos
    }

    /**
     * Realizar búsqueda de sección específica
     */
    private function performSectionSearch(string $sectionSlug, int $limit, int $offset, string $sortBy, Request $request): JsonResponse
    {
        // Obtener información de la sección
        $sectionInfo = $this->getSectionInfo($sectionSlug);
        
        if (!$sectionInfo) {
            return $this->notFoundResponse('Sección no encontrada');
        }

        // Construir query base
        $query = Destino::where('status', 'published')
            ->with([
                'region:id,name',
                'imagenes' => function ($q) { $q->main(); },
                'categorias:id,name,icon',
                'caracteristicas' => function ($q) { $q->activas(); },
                'tags:id,name,color'
            ]);

        // Aplicar filtros según la sección
        $this->applySectionFilters($query, $sectionSlug);

        // Aplicar ordenamiento
        $this->applySorting($query, $sortBy, $request);

        // Obtener total de destinos
        $totalDestinations = $query->count();

        // Aplicar paginación
        $destinations = $query->skip($offset)->take($limit)->get();

        // Transformar respuesta
        $destinationsData = $destinations->map(function ($destino) {
            return [
                'id' => $destino->id,
                'titulo' => $destino->name,
                'slug' => $destino->slug,
                'region' => $destino->region ? $destino->region->name : null,
                'imagen_principal' => $destino->imagenes->first() ? $destino->imagenes->first()->url : null,
                'rating' => $destino->average_rating,
                'reviews_count' => $destino->reviews_count,
                'descripcion_corta' => $destino->descripcion_corta ?? $destino->short_description,
                'tags' => $destino->tags->pluck('name'),
                'caracteristicas' => $destino->caracteristicas->pluck('nombre'),
                'categorias' => $destino->categorias->pluck('name'),
                'lat' => $destino->latitude,
                'lng' => $destino->longitude,
                'distancia_km' => isset($destino->distancia_km) ? round($destino->distancia_km, 2) : null,
                'is_top' => $destino->is_top,
                'is_featured' => $destino->is_featured,
            ];
        });

        $results = [
            'section' => array_merge($sectionInfo, ['total_destinations' => $totalDestinations]),
            'destinations' => $destinationsData,
            'pagination' => [
                'current_page' => ($offset / $limit) + 1,
                'per_page' => $limit,
                'total' => $totalDestinations,
                'has_more' => ($offset + $limit) < $totalDestinations,
            ],
        ];

        return $this->successResponse($results, 'Sección recuperada exitosamente.');
    }

    /**
     * Obtener lista de todas las secciones
     */
    private function performSectionsList(): JsonResponse
    {
        $sections = [
            [
                'slug' => 'pueblos-magicos',
                'title' => 'Pueblos Mágicos',
                'subtitle' => 'Descubre la magia de nuestros pueblos',
                'description' => 'Explora los encantadores pueblos mágicos de Hidalgo, donde la tradición y la cultura cobran vida.',
                'total_destinations' => Destino::where('status', 'published')
                    ->whereHas('categorias', function ($q) {
                        $q->where('name', 'like', '%Pueblo Mágico%');
                    })->count(),
                'image' => null,
            ],
            [
                'slug' => 'aventura',
                'title' => 'Aventura',
                'subtitle' => 'Experiencias llenas de adrenalina',
                'description' => 'Vive emocionantes aventuras en los paisajes naturales de Hidalgo.',
                'total_destinations' => Destino::where('status', 'published')
                    ->whereHas('categorias', function ($q) {
                        $q->where('name', 'like', '%Aventura%');
                    })->count(),
                'image' => null,
            ],
            [
                'slug' => 'cultura',
                'title' => 'Cultura',
                'subtitle' => 'Sumérgete en nuestra rica historia',
                'description' => 'Descubre el patrimonio cultural e histórico de Hidalgo.',
                'total_destinations' => Destino::where('status', 'published')
                    ->whereHas('categorias', function ($q) {
                        $q->where('name', 'like', '%Cultura%');
                    })->count(),
                'image' => null,
            ],
            [
                'slug' => 'gastronomia',
                'title' => 'Gastronomía',
                'subtitle' => 'Sabores únicos de la región',
                'description' => 'Disfruta de la deliciosa gastronomía tradicional de Hidalgo.',
                'total_destinations' => Destino::where('status', 'published')
                    ->whereHas('caracteristicas', function ($q) {
                        $q->where('nombre', 'like', '%Gastronomía%');
                    })->count(),
                'image' => null,
            ],
            [
                'slug' => 'naturaleza',
                'title' => 'Naturaleza',
                'subtitle' => 'Conecta con el medio ambiente',
                'description' => 'Explora los hermosos paisajes naturales y reservas ecológicas.',
                'total_destinations' => Destino::where('status', 'published')
                    ->whereHas('caracteristicas', function ($q) {
                        $q->where('nombre', 'like', '%Naturaleza%');
                    })->count(),
                'image' => null,
            ],
        ];

        return $this->successResponse($sections, 'Secciones recuperadas exitosamente.');
    }

    /**
     * Obtener información de una sección específica
     */
    private function getSectionInfo(string $sectionSlug): ?array
    {
        $sections = [
            'pueblos-magicos' => [
                'slug' => 'pueblos-magicos',
                'title' => 'Pueblos Mágicos',
                'subtitle' => 'Descubre la magia de nuestros pueblos',
                'description' => 'Explora los encantadores pueblos mágicos de Hidalgo, donde la tradición y la cultura cobran vida.',
                'image' => null,
            ],
            'aventura' => [
                'slug' => 'aventura',
                'title' => 'Aventura',
                'subtitle' => 'Experiencias llenas de adrenalina',
                'description' => 'Vive emocionantes aventuras en los paisajes naturales de Hidalgo.',
                'image' => null,
            ],
            'cultura' => [
                'slug' => 'cultura',
                'title' => 'Cultura',
                'subtitle' => 'Sumérgete en nuestra rica historia',
                'description' => 'Descubre el patrimonio cultural e histórico de Hidalgo.',
                'image' => null,
            ],
            'gastronomia' => [
                'slug' => 'gastronomia',
                'title' => 'Gastronomía',
                'subtitle' => 'Sabores únicos de la región',
                'description' => 'Disfruta de la deliciosa gastronomía tradicional de Hidalgo.',
                'image' => null,
            ],
            'naturaleza' => [
                'slug' => 'naturaleza',
                'title' => 'Naturaleza',
                'subtitle' => 'Conecta con el medio ambiente',
                'description' => 'Explora los hermosos paisajes naturales y reservas ecológicas.',
                'image' => null,
            ],
        ];

        return $sections[$sectionSlug] ?? null;
    }

    /**
     * Aplicar filtros según la sección
     */
    private function applySectionFilters($query, string $sectionSlug): void
    {
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
    }

    /**
     * Aplicar ordenamiento
     */
    private function applySorting($query, string $sortBy, Request $request): void
    {
        switch ($sortBy) {
            case 'rating':
                $query->orderByDesc('average_rating');
                break;
            case 'popularidad':
                $query->orderByDesc('reviews_count');
                break;
            case 'nuevos':
                $query->latest();
                break;
            case 'distancia':
                if ($request->filled('latitude') && $request->filled('longitude')) {
                    $latitude = $request->input('latitude');
                    $longitude = $request->input('longitude');
                    $query->withDistance($latitude, $longitude)
                          ->orderBy('distancia_km', 'asc');
                } else {
                    $query->orderByDesc('average_rating');
                }
                break;
            default:
                $query->orderByDesc('average_rating');
                break;
        }
    }
} 