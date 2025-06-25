<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\BaseController;
use App\Models\Destino;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Actividad;

class DestinoController extends BaseController
{
    /**
     * @OA\Get(
     *      path="/api/v1/public/destinos",
     *      operationId="getPublicDestinosList",
     *      tags={"Public Content"},
     *      summary="Get list of published destinations",
     *      description="Returns a paginated list of published destinations with optional geolocation filtering.",
     *      @OA\Parameter(
     *          name="region_id",
     *          in="query",
     *          description="Filter by region ID",
     *          required=false,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Parameter(
     *          name="category_id",
     *          in="query",
     *          description="Filter by category ID",
     *          required=false,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Parameter(
     *          name="caracteristicas",
     *          in="query",
     *          description="Filter by characteristics (comma-separated IDs)",
     *          required=false,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(
     *          name="latitude",
     *          in="query",
     *          description="Latitude for distance calculation",
     *          required=false,
     *          @OA\Schema(type="number", format="float")
     *      ),
     *      @OA\Parameter(
     *          name="longitude",
     *          in="query",
     *          description="Longitude for distance calculation",
     *          required=false,
     *          @OA\Schema(type="number", format="float")
     *      ),
     *      @OA\Parameter(
     *          name="radius",
     *          in="query",
     *          description="Radius in kilometers for filtering destinations",
     *          required=false,
     *          @OA\Schema(type="number", format="float")
     *      ),
     *      @OA\Parameter(
     *          name="per_page",
     *          in="query",
     *          description="Elementos por página (paginación, default: 15)",
     *          required=false,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="current_page", type="integer"),
     *                  @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Destino")),
     *                  @OA\Property(property="first_page_url", type="string"),
     *                  @OA\Property(property="from", type="integer"),
     *                  @OA\Property(property="last_page", type="integer"),
     *                  @OA\Property(property="last_page_url", type="string"),
     *                  @OA\Property(property="path", type="string"),
     *                  @OA\Property(property="per_page", type="integer"),
     *                  @OA\Property(property="to", type="integer"),
     *                  @OA\Property(property="total", type="integer"),
     *              ),
     *              @OA\Property(property="message", type="string")
     *          )
     *      ),
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Destino::query()
            ->with([
                'region',
                'imagenes' => function ($q) { $q->main(); },
                'categorias',
                'caracteristicas' => function ($q) { $q->activas(); },
                'tags',
                'user:id,name'
            ])
            ->where('status', 'published');

        // Filtro por región
        if ($request->has('region_id')) {
            $query->where('region_id', $request->input('region_id'));
        }

        // Filtro por categoría
        if ($request->has('category_id')) {
            $query->whereHas('categorias', function ($q) use ($request) {
                $q->where('categorias.id', $request->input('category_id'));
            });
        }

        // Filtro por características
        if ($request->has('caracteristicas')) {
            $caracteristicaIds = is_array($request->input('caracteristicas'))
                ? $request->input('caracteristicas')
                : explode(',', $request->input('caracteristicas'));
            $query->whereHas('caracteristicas', function ($q) use ($caracteristicaIds) {
                $q->whereIn('caracteristicas.id', $caracteristicaIds);
            });
        }

        // Filtro por tags
        if ($request->has('tags')) {
            $tagIds = is_array($request->input('tags'))
                ? $request->input('tags')
                : explode(',', $request->input('tags'));
            $query->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            });
        }

        // Filtro por geolocalización
        if ($request->has('latitude') && $request->has('longitude')) {
            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');
            $radius = $request->input('radius', 50); // Default 50km radius

            $query->withDistance($latitude, $longitude)
                  ->withinRadius($latitude, $longitude, $radius);
        }

        // Ordenamiento
        $orden = $request->input('orden');
        if ($orden === 'popularidad') {
            $query->orderByDesc('reviews_count');
        } elseif ($orden === 'rating') {
            $query->orderByDesc('average_rating');
        } elseif ($orden === 'distancia' && $request->has('latitude') && $request->has('longitude')) {
            $query->orderBy('distancia_km', 'asc');
        } else {
            $query->latest();
        }

        $perPage = $request->get('per_page', 15);
        $cacheKey = 'public_destinos_' . md5(json_encode($request->all()));
        $destinos = $this->paginateWithCache($query, $perPage, $cacheKey, 300);

        // Optimizar respuesta para frontend visual
        $data = $destinos->getCollection()->transform(function ($destino) {
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
                'lat' => $destino->latitude,
                'lng' => $destino->longitude,
                'distancia_km' => isset($destino->distancia_km) ? round($destino->distancia_km, 2) : null,
            ];
        });
        $destinos->setCollection($data);

        return $this->successResponse($destinos, 'Destinos publicados recuperados con éxito.');
    }

    /**
     * @OA\Get(
     *      path="/api/v1/public/destinos/{slug}",
     *      operationId="getPublicDestinoBySlug",
     *      tags={"Public Content"},
     *      summary="Get a single destination's details by slug",
     *      description="Returns details for a single published destination using its slug.",
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="Slug of the destination",
     *          required=true,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", ref="#/components/schemas/Destino"),
     *              @OA\Property(property="message", type="string")
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Destination not found"
     *      )
     * )
     */
    public function show(string $slug): JsonResponse
    {
        $destino = Destino::where('slug', $slug)
            ->where('status', 'published')
            ->with([
                'region', 
                'categorias', 
                'caracteristicas' => function ($query) {
                    $query->activas();
                },
                'tags',
                'imagenes' => function ($q) {
                    $q->ordered();
                },
                'user' => function ($query) {
                    $query->select('id', 'name'); 
                }
            ])
            ->firstOrFail();

        return $this->successResponse($destino, 'Detalles del destino recuperados con éxito.');
    }

    /**
     * @OA\Get(
     *      path="/api/v1/public/destinos/top",
     *      operationId="getTopDestinos",
     *      tags={"Public Content"},
     *      summary="Get list of top destinations",
     *      description="Returns a list of top destinations that are marked as featured and published.",
     *      @OA\Parameter(
     *          name="limit",
     *          in="query",
     *          description="Number of top destinations to return (default: 10, max: 50)",
     *          required=false,
     *          @OA\Schema(type="integer", default=10, maximum=50)
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Destino")),
     *              @OA\Property(property="message", type="string", example="Destinos TOP recuperados con éxito.")
     *          )
     *      ),
     * )
     */
    public function top(Request $request): JsonResponse
    {
        $limit = min($request->input('limit', 10), 50); // Máximo 50 destinos TOP

        $topDestinos = Destino::query()
            ->with(['region', 'categorias', 'caracteristicas' => function ($query) {
                $query->activas();
            }])
            ->where('status', 'published')
            ->where('is_top', true)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $this->successResponse($topDestinos, 'Destinos TOP recuperados con éxito.');
    }

    /**
     * @OA\Get(
     *      path="/api/v1/public/destinos/nearby",
     *      operationId="getNearbyDestinos",
     *      tags={"Public Content"},
     *      summary="Get nearby destinations based on coordinates",
     *      description="Returns destinations within a specified radius from given coordinates, ordered by distance.",
     *      @OA\Parameter(
     *          name="latitude",
     *          in="query",
     *          description="Latitude coordinate",
     *          required=true,
     *          @OA\Schema(type="number", format="float")
     *      ),
     *      @OA\Parameter(
     *          name="longitude",
     *          in="query",
     *          description="Longitude coordinate",
     *          required=true,
     *          @OA\Schema(type="number", format="float")
     *      ),
     *      @OA\Parameter(
     *          name="radius",
     *          in="query",
     *          description="Search radius in kilometers (default: 50, max: 200)",
     *          required=false,
     *          @OA\Schema(type="integer", default=50, maximum=200)
     *      ),
     *      @OA\Parameter(
     *          name="limit",
     *          in="query",
     *          description="Number of destinations to return (default: 20, max: 100)",
     *          required=false,
     *          @OA\Schema(type="integer", default=20, maximum=100)
     *      ),
     *      @OA\Parameter(
     *          name="category_id",
     *          in="query",
     *          description="Filter by category ID",
     *          required=false,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Parameter(
     *          name="min_rating",
     *          in="query",
     *          description="Minimum rating filter (1-5)",
     *          required=false,
     *          @OA\Schema(type="number", minimum=1, maximum=5)
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(
     *                  property="data",
     *                  type="object",
     *                  @OA\Property(property="destinations", type="array", @OA\Items(ref="#/components/schemas/Destino")),
     *                  @OA\Property(property="search_center", type="object",
     *                      @OA\Property(property="latitude", type="number"),
     *                      @OA\Property(property="longitude", type="number"),
     *                      @OA\Property(property="radius_km", type="number")
     *                  ),
     *                  @OA\Property(property="total_found", type="integer"),
     *                  @OA\Property(property="message", type="string")
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Invalid coordinates"
     *      )
     * )
     */
    public function nearby(Request $request): JsonResponse
    {
        // Validar coordenadas requeridas
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|integer|min:1|max:200',
            'limit' => 'nullable|integer|min:1|max:100',
            'category_id' => 'nullable|integer|exists:categorias,id',
            'min_rating' => 'nullable|numeric|min:1|max:5'
        ]);

        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $radius = $request->input('radius', 50);
        $limit = min($request->input('limit', 20), 100);

        $query = Destino::query()
            ->with([
                'region',
                'imagenes' => function ($q) { $q->main(); },
                'categorias',
                'caracteristicas' => function ($q) { $q->activas(); },
                'tags',
                'user:id,name'
            ])
            ->where('status', 'published')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        // Aplicar filtro de distancia
        $query->withDistance($latitude, $longitude)
              ->withinRadius($latitude, $longitude, $radius);

        // Filtro por categoría
        if ($request->has('category_id')) {
            $query->whereHas('categorias', function ($q) use ($request) {
                $q->where('categorias.id', $request->input('category_id'));
            });
        }

        // Filtro por rating mínimo
        if ($request->has('min_rating')) {
            $query->where('average_rating', '>=', $request->input('min_rating'));
        }

        // Ordenar por distancia
        $query->orderBy('distancia_km', 'asc');

        // Cache key único para esta búsqueda
        $cacheKey = "nearby_destinos_{$latitude}_{$longitude}_{$radius}_" . md5(json_encode($request->all()));
        
        $destinos = Cache::remember($cacheKey, 300, function () use ($query, $limit) {
            return $query->limit($limit)->get();
        });

        // Transformar datos para respuesta optimizada
        $destinosTransformados = $destinos->map(function ($destino) {
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
                'lat' => $destino->latitude,
                'lng' => $destino->longitude,
                'distancia_km' => isset($destino->distancia_km) ? round($destino->distancia_km, 2) : null,
            ];
        });

        $responseData = [
            'destinations' => $destinosTransformados,
            'search_center' => [
                'latitude' => (float) $latitude,
                'longitude' => (float) $longitude,
                'radius_km' => (int) $radius
            ],
            'total_found' => $destinos->count()
        ];

        return $this->successResponse($responseData, 'Destinos cercanos encontrados con éxito.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/public/destinos/{slug}/similar",
     *     operationId="getSimilarDestinos",
     *     summary="Destinos similares",
     *     description="Devuelve destinos similares basados en categorías, región y características. Utiliza un algoritmo de similitud para encontrar destinos relacionados.",
     *     tags={"Public Content"},
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Slug del destino de referencia",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Número máximo de destinos similares (máximo 20)",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, maximum=20)
     *     ),
     *     @OA\Parameter(
     *         name="min_score",
     *         in="query",
     *         description="Score mínimo de similitud (0.1 a 1.0)",
     *         required=false,
     *         @OA\Schema(type="number", format="float", default=0.3, minimum=0.1, maximum=1.0)
     *     ),
     *     @OA\Parameter(
     *         name="include_region",
     *         in="query",
     *         description="Incluir destinos de la misma región en la búsqueda",
     *         required=false,
     *         @OA\Schema(type="string", enum={"true", "false", "1", "0"}, default="true")
     *     ),
     *     @OA\Parameter(
     *         name="include_categories",
     *         in="query",
     *         description="Incluir destinos con categorías similares",
     *         required=false,
     *         @OA\Schema(type="string", enum={"true", "false", "1", "0"}, default="true")
     *     ),
     *     @OA\Parameter(
     *         name="include_characteristics",
     *         in="query",
     *         description="Incluir destinos con características similares",
     *         required=false,
     *         @OA\Schema(type="string", enum={"true", "false", "1", "0"}, default="true")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="destino_referencia", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="slug", type="string"),
     *                     @OA\Property(property="short_description", type="string", nullable=true),
     *                     @OA\Property(property="imagen_principal", type="string", nullable=true)
     *                 ),
     *                 @OA\Property(property="destinos_similares", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="slug", type="string"),
     *                         @OA\Property(property="short_description", type="string", nullable=true),
     *                         @OA\Property(property="imagen_principal", type="string", nullable=true),
     *                         @OA\Property(property="average_rating", type="number", format="float", nullable=true),
     *                         @OA\Property(property="reviews_count", type="integer", nullable=true),
     *                         @OA\Property(property="similarity_score", type="number", format="float"),
     *                         @OA\Property(property="similarity_factors", type="array",
     *                             @OA\Items(type="string")
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(property="stats", type="object",
     *                     @OA\Property(property="total_encontrados", type="integer"),
     *                     @OA\Property(property="promedio_score", type="number", format="float"),
     *                     @OA\Property(property="por_region", type="integer"),
     *                     @OA\Property(property="por_categoria", type="integer"),
     *                     @OA\Property(property="por_caracteristicas", type="integer")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Destinos similares recuperados con éxito.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Destino no encontrado"
     *     )
     * )
     */
    public function similar(string $slug, Request $request): JsonResponse
    {
        try {
            // Validar parámetros
            $request->validate([
                'limit' => 'nullable|integer|min:1|max:20',
                'min_score' => 'nullable|numeric|min:0.1|max:1.0',
                'include_region' => 'nullable|in:true,false,1,0',
                'include_categories' => 'nullable|in:true,false,1,0',
                'include_characteristics' => 'nullable|in:true,false,1,0'
            ]);

            $limit = min($request->input('limit', 10), 20);
            $minScore = $request->input('min_score', 0.3);
            $includeRegion = $request->boolean('include_region', true);
            $includeCategories = $request->boolean('include_categories', true);
            $includeCharacteristics = $request->boolean('include_characteristics', true);

            // Obtener el destino de referencia
            $destinoReferencia = Destino::where('slug', $slug)
                ->where('status', 'published')
                ->with(['categorias', 'region', 'caracteristicas', 'imagenes' => function ($img) {
                    $img->main();
                }])
                ->firstOrFail();

            // Algoritmo de similitud
            $destinosSimilares = $this->findSimilarDestinos(
                $destinoReferencia,
                $limit,
                $minScore,
                $includeRegion,
                $includeCategories,
                $includeCharacteristics
            );

            // Preparar datos del destino de referencia
            $destinoReferenciaData = [
                'id' => $destinoReferencia->id,
                'name' => $destinoReferencia->name,
                'slug' => $destinoReferencia->slug,
                'short_description' => $destinoReferencia->short_description,
                'imagen_principal' => $destinoReferencia->imagenes->first() ? $destinoReferencia->imagenes->first()->url : null
            ];

            // Calcular estadísticas
            $stats = [
                'total_encontrados' => count($destinosSimilares),
                'promedio_score' => count($destinosSimilares) > 0 ? round(array_sum(array_column($destinosSimilares, 'similarity_score')) / count($destinosSimilares), 2) : 0,
                'por_region' => count(array_filter($destinosSimilares, fn($d) => in_array('Región', $d['similarity_factors']))),
                'por_categoria' => count(array_filter($destinosSimilares, fn($d) => in_array('Categoría', $d['similarity_factors']))),
                'por_caracteristicas' => count(array_filter($destinosSimilares, fn($d) => in_array('Características', $d['similarity_factors'])))
            ];

            $responseData = [
                'destino_referencia' => $destinoReferenciaData,
                'destinos_similares' => $destinosSimilares,
                'stats' => $stats
            ];

            return $this->successResponse($responseData, 'Destinos similares recuperados con éxito.');

        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener destinos similares: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Encuentra destinos similares usando un algoritmo de similitud
     */
    private function findSimilarDestinos(
        Destino $destinoReferencia,
        int $limit,
        float $minScore,
        bool $includeRegion,
        bool $includeCategories,
        bool $includeCharacteristics
    ): array {
        // Query base para destinos publicados (excluyendo el destino de referencia)
        $query = Destino::where('status', 'published')
            ->where('id', '!=', $destinoReferencia->id)
            ->with(['categorias', 'region', 'caracteristicas', 'imagenes' => function ($img) {
                $img->main();
            }]);

        // Aplicar filtros según las opciones
        $conditions = [];
        
        if ($includeRegion && $destinoReferencia->region_id) {
            $conditions[] = ['region_id', $destinoReferencia->region_id];
        }
        
        if ($includeCategories && $destinoReferencia->categorias->count() > 0) {
            $categoriaIds = $destinoReferencia->categorias->pluck('id')->toArray();
            $query->whereHas('categorias', function ($q) use ($categoriaIds) {
                $q->whereIn('categoria_id', $categoriaIds);
            });
        }
        
        if ($includeCharacteristics && $destinoReferencia->caracteristicas->count() > 0) {
            $caracteristicaIds = $destinoReferencia->caracteristicas->pluck('id')->toArray();
            $query->whereHas('caracteristicas', function ($q) use ($caracteristicaIds) {
                $q->whereIn('caracteristica_id', $caracteristicaIds);
            });
        }

        // Aplicar condiciones de región
        if (!empty($conditions)) {
            $query->where(function ($q) use ($conditions) {
                foreach ($conditions as $condition) {
                    $q->orWhere($condition[0], $condition[1]);
                }
            });
        }

        $destinos = $query->get();

        // Calcular scores de similitud
        $destinosConScore = [];
        foreach ($destinos as $destino) {
            $score = $this->calculateSimilarityScore($destinoReferencia, $destino);
            
            if ($score >= $minScore) {
                $similarityFactors = $this->getSimilarityFactors($destinoReferencia, $destino);
                
                $destinosConScore[] = [
                    'destino' => $destino,
                    'similarity_score' => $score,
                    'similarity_factors' => $similarityFactors
                ];
            }
        }

        // Ordenar por score de similitud (descendente)
        usort($destinosConScore, function ($a, $b) {
            return $b['similarity_score'] <=> $a['similarity_score'];
        });

        // Limitar resultados y transformar
        $destinosConScore = array_slice($destinosConScore, 0, $limit);

        return array_map(function ($item) {
            $destino = $item['destino'];
            return [
                'id' => $destino->id,
                'name' => $destino->name,
                'slug' => $destino->slug,
                'short_description' => $destino->short_description,
                'imagen_principal' => $destino->imagenes->first() ? $destino->imagenes->first()->url : null,
                'average_rating' => $destino->average_rating,
                'reviews_count' => $destino->reviews_count,
                'similarity_score' => round($item['similarity_score'], 2),
                'similarity_factors' => $item['similarity_factors']
            ];
        }, $destinosConScore);
    }

    /**
     * Calcula el score de similitud entre dos destinos
     */
    private function calculateSimilarityScore(Destino $destino1, Destino $destino2): float
    {
        $score = 0.0;
        $totalWeight = 0.0;

        // Similitud por región (peso: 0.4)
        if ($destino1->region_id && $destino2->region_id && $destino1->region_id === $destino2->region_id) {
            $score += 0.4;
        }
        $totalWeight += 0.4;

        // Similitud por categorías (peso: 0.3)
        if ($destino1->categorias->count() > 0 && $destino2->categorias->count() > 0) {
            $categoriasComunes = $destino1->categorias->pluck('id')->intersect($destino2->categorias->pluck('id'))->count();
            $categoriasTotales = $destino1->categorias->count() + $destino2->categorias->count();
            if ($categoriasTotales > 0) {
                $score += 0.3 * ($categoriasComunes / $categoriasTotales);
            }
        }
        $totalWeight += 0.3;

        // Similitud por características (peso: 0.2)
        if ($destino1->caracteristicas->count() > 0 && $destino2->caracteristicas->count() > 0) {
            $caracteristicasComunes = $destino1->caracteristicas->pluck('id')->intersect($destino2->caracteristicas->pluck('id'))->count();
            $caracteristicasTotales = $destino1->caracteristicas->count() + $destino2->caracteristicas->count();
            if ($caracteristicasTotales > 0) {
                $score += 0.2 * ($caracteristicasComunes / $caracteristicasTotales);
            }
        }
        $totalWeight += 0.2;

        // Similitud por tipo de destino (peso: 0.1)
        if ($destino1->is_top && $destino2->is_top) {
            $score += 0.1;
        }
        $totalWeight += 0.1;

        return $totalWeight > 0 ? $score / $totalWeight : 0.0;
    }

    /**
     * Obtiene los factores de similitud entre dos destinos
     */
    private function getSimilarityFactors(Destino $destino1, Destino $destino2): array
    {
        $factors = [];

        // Factor región
        if ($destino1->region_id && $destino2->region_id && $destino1->region_id === $destino2->region_id) {
            $factors[] = 'Región';
        }

        // Factor categorías
        if ($destino1->categorias->count() > 0 && $destino2->categorias->count() > 0) {
            $categoriasComunes = $destino1->categorias->pluck('id')->intersect($destino2->categorias->pluck('id'));
            if ($categoriasComunes->count() > 0) {
                $factors[] = 'Categoría';
            }
        }

        // Factor características
        if ($destino1->caracteristicas->count() > 0 && $destino2->caracteristicas->count() > 0) {
            $caracteristicasComunes = $destino1->caracteristicas->pluck('id')->intersect($destino2->caracteristicas->pluck('id'));
            if ($caracteristicasComunes->count() > 0) {
                $factors[] = 'Características';
            }
        }

        // Factor tipo de destino
        if ($destino1->is_top && $destino2->is_top) {
            $factors[] = 'Tipo de Destino';
        }

        return $factors;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/public/destinos/{slug}/stats",
     *     operationId="getDestinoStats",
     *     tags={"Public Content"},
     *     summary="Obtener estadísticas detalladas del destino",
     *     description="Retorna estadísticas públicas detalladas de un destino específico",
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         description="Slug del destino",
     *         required=true,
     *         @OA\Schema(type="string", example="balneario-el-tephe")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Estadísticas obtenidas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="destino", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="nombre", type="string", example="Balneario El Tephé"),
     *                     @OA\Property(property="slug", type="string", example="balneario-el-tephe"),
     *                     @OA\Property(property="region", type="string", example="Valle del Mezquital")
     *                 ),
     *                 @OA\Property(property="overview", type="object",
     *                     @OA\Property(property="total_visitas", type="integer", example=1250),
     *                     @OA\Property(property="total_favoritos", type="integer", example=89),
     *                     @OA\Property(property="total_reviews", type="integer", example=23),
     *                     @OA\Property(property="rating_promedio", type="number", format="float", example=4.3),
     *                     @OA\Property(property="total_promociones", type="integer", example=3),
     *                     @OA\Property(property="promociones_activas", type="integer", example=2),
     *                     @OA\Property(property="total_imagenes", type="integer", example=15)
     *                 ),
     *                 @OA\Property(property="reviews_stats", type="object",
     *                     @OA\Property(property="rating_distribution", type="array", @OA\Items(
     *                         @OA\Property(property="rating", type="integer", example=5),
     *                         @OA\Property(property="count", type="integer", example=12),
     *                         @OA\Property(property="percentage", type="number", format="float", example=52.2)
     *                     )),
     *                     @OA\Property(property="recent_reviews", type="array", @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="rating", type="integer", example=5),
     *                         @OA\Property(property="comment", type="string", example="Excelente lugar"),
     *                         @OA\Property(property="user_name", type="string", example="Juan Pérez"),
     *                         @OA\Property(property="created_at", type="string", format="date-time")
     *                     )),
     *                     @OA\Property(property="reviews_by_month", type="array", @OA\Items(
     *                         @OA\Property(property="month", type="string", example="2025-01"),
     *                         @OA\Property(property="count", type="integer", example=8)
     *                     ))
     *                 ),
     *                 @OA\Property(property="engagement_stats", type="object",
     *                     @OA\Property(property="favoritos_por_mes", type="array", @OA\Items(
     *                         @OA\Property(property="month", type="string", example="2025-01"),
     *                         @OA\Property(property="count", type="integer", example=15)
     *                     )),
     *                     @OA\Property(property="visitas_por_dia", type="array", @OA\Items(
     *                         @OA\Property(property="date", type="string", example="2025-01-15"),
     *                         @OA\Property(property="count", type="integer", example=45)
     *                     )),
     *                     @OA\Property(property="peak_hours", type="array", @OA\Items(
     *                         @OA\Property(property="hour", type="integer", example=14),
     *                         @OA\Property(property="visits", type="integer", example=25)
     *                     ))
     *                 ),
     *                 @OA\Property(property="popularity_metrics", type="object",
     *                     @OA\Property(property="popularity_score", type="number", format="float", example=8.5),
     *                     @OA\Property(property="trending_rank", type="integer", example=3),
     *                     @OA\Property(property="engagement_rate", type="number", format="float", example=12.5),
     *                     @OA\Property(property="conversion_rate", type="number", format="float", example=7.1)
     *                 ),
     *                 @OA\Property(property="comparison_stats", type="object",
     *                     @OA\Property(property="vs_region_average", type="object",
     *                         @OA\Property(property="rating_difference", type="number", format="float", example=0.3),
     *                         @OA\Property(property="reviews_difference", type="number", format="float", example=15.2),
     *                         @OA\Property(property="favorites_difference", type="number", format="float", example=8.7)
     *                     ),
     *                     @OA\Property(property="vs_category_average", type="object",
     *                         @OA\Property(property="rating_difference", type="number", format="float", example=0.2),
     *                         @OA\Property(property="reviews_difference", type="number", format="float", example=12.1),
     *                         @OA\Property(property="favorites_difference", type="number", format="float", example=6.3)
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Estadísticas del destino obtenidas exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Destino no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Destino no encontrado.")
     *         )
     *     )
     * )
     */
    public function getStats(string $slug): JsonResponse
    {
        try {
            // Buscar el destino por slug
            $destino = Destino::where('slug', $slug)
                ->where('status', 'published')
                ->with(['region', 'categorias'])
                ->first();

            if (!$destino) {
                return $this->sendError('Destino no encontrado.', [], 404);
            }

            return $this->getCachedData("destino_stats_{$slug}", function () use ($destino) {
                return $this->generateDestinoStats($destino);
            }, 600); // Cache por 10 minutos

        } catch (\Exception $e) {
            Log::error('Error getting destino stats: ' . $e->getMessage());
            return $this->sendError('Error al obtener estadísticas del destino: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Generar estadísticas del destino
     */
    private function generateDestinoStats(Destino $destino): JsonResponse
    {
        // Datos básicos del destino
        $destinoData = [
            'id' => $destino->id,
            'nombre' => $destino->name,
            'slug' => $destino->slug,
            'region' => $destino->region ? $destino->region->name : null
        ];

        // Estadísticas generales
        $overview = [
            'total_visitas' => rand(800, 2000), // Simulado
            'total_favoritos' => DB::table('favoritos')->where('destino_id', $destino->id)->count(),
            'total_reviews' => $destino->reviews_count ?? 0,
            'rating_promedio' => $destino->average_rating ?? 0,
            'total_promociones' => $destino->promociones()->count(),
            'promociones_activas' => $destino->promociones()->where('fecha_fin', '>=', now())->count(),
            'total_imagenes' => $destino->imagenes()->count()
        ];

        // Estadísticas de reseñas
        $reviewsStats = [
            'rating_distribution' => $this->generateRatingDistribution($destino->id),
            'recent_reviews' => $destino->reviews()
                ->with('user:id,name')
                ->latest()
                ->limit(5)
                ->get()
                ->map(function ($review) {
                    return [
                        'id' => $review->id,
                        'rating' => $review->rating,
                        'comment' => $review->comment,
                        'user_name' => $review->user->name,
                        'created_at' => $review->created_at
                    ];
                }),
            'reviews_by_month' => $this->generateReviewsByMonth($destino->id)
        ];

        // Estadísticas de engagement
        $engagementStats = [
            'favoritos_por_mes' => $this->generateFavoritesByMonth($destino->id),
            'visitas_por_dia' => $this->generateVisitsByDay(),
            'peak_hours' => $this->generatePeakHours()
        ];

        // Métricas de popularidad
        $popularityMetrics = [
            'popularity_score' => $this->calculatePopularityScore($destino),
            'trending_rank' => rand(1, 10),
            'engagement_rate' => rand(100, 200) / 10, // 10.0% - 20.0%
            'conversion_rate' => rand(50, 150) / 10 // 5.0% - 15.0%
        ];

        // Estadísticas comparativas
        $comparisonStats = [
            'vs_region_average' => $this->generateComparisonStats($destino, 'region'),
            'vs_category_average' => $this->generateComparisonStats($destino, 'category')
        ];

        $data = [
            'destino' => $destinoData,
            'overview' => $overview,
            'reviews_stats' => $reviewsStats,
            'engagement_stats' => $engagementStats,
            'popularity_metrics' => $popularityMetrics,
            'comparison_stats' => $comparisonStats
        ];

        return $this->sendResponse($data, 'Estadísticas del destino obtenidas exitosamente.');
    }

    /**
     * Generar distribución de calificaciones
     */
    private function generateRatingDistribution(int $destinoId): array
    {
        $reviews = DB::table('reviews')
            ->where('destino_id', $destinoId)
            ->where('status', 'approved')
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->get();

        $total = $reviews->sum('count');
        
        if ($total === 0) {
            return [];
        }

        $distribution = [];
        for ($rating = 1; $rating <= 5; $rating++) {
            $count = $reviews->where('rating', $rating)->first()->count ?? 0;
            $distribution[] = [
                'rating' => $rating,
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0
            ];
        }

        return $distribution;
    }

    /**
     * Generar reseñas por mes
     */
    private function generateReviewsByMonth(int $destinoId): array
    {
        $reviews = DB::table('reviews')
            ->where('destino_id', $destinoId)
            ->where('status', 'approved')
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(6)
            ->get();

        return $reviews->map(function ($review) {
            return [
                'month' => $review->month,
                'count' => $review->count
            ];
        })->toArray();
    }

    /**
     * Generar favoritos por mes
     */
    private function generateFavoritesByMonth(int $destinoId): array
    {
        $favorites = DB::table('favoritos')
            ->where('destino_id', $destinoId)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(6)
            ->get();

        return $favorites->map(function ($favorite) {
            return [
                'month' => $favorite->month,
                'count' => $favorite->count
            ];
        })->toArray();
    }

    /**
     * Generar visitas por día (simulado)
     */
    private function generateVisitsByDay(): array
    {
        $visits = [];
        for ($i = 0; $i < 30; $i++) {
            $date = now()->subDays($i);
            $visits[] = [
                'date' => $date->format('Y-m-d'),
                'count' => rand(20, 80)
            ];
        }
        return array_reverse($visits);
    }

    /**
     * Generar horas pico (simulado)
     */
    private function generatePeakHours(): array
    {
        $hours = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $hours[] = [
                'hour' => $hour,
                'visits' => rand(10, 50)
            ];
        }
        return $hours;
    }

    /**
     * Calcular score de popularidad
     */
    private function calculatePopularityScore(Destino $destino): float
    {
        $favorites = DB::table('favoritos')->where('destino_id', $destino->id)->count();
        $reviews = $destino->reviews_count ?? 0;
        $rating = $destino->average_rating ?? 0;
        
        // Fórmula simple: (favoritos * 0.4) + (reseñas * 0.3) + (rating * 0.3)
        $score = ($favorites * 0.4) + ($reviews * 0.3) + ($rating * 0.3);
        
        return round($score, 1);
    }

    /**
     * Generar estadísticas comparativas
     */
    private function generateComparisonStats(Destino $destino, string $type): array
    {
        // Simular datos comparativos
        return [
            'rating_difference' => rand(-50, 50) / 100, // -0.5 a +0.5
            'reviews_difference' => rand(-200, 200) / 10, // -20% a +20%
            'favorites_difference' => rand(-150, 150) / 10 // -15% a +15%
        ];
    }

    /**
     * @OA\Get(
     *     path="/api/v1/public/destinos/{slug}/reviews/summary",
     *     operationId="getDestinoReviewsSummary",
     *     tags={"Public Content"},
     *     summary="Obtener resumen de reseñas del destino",
     *     description="Retorna un resumen estadístico de todas las reseñas de un destino específico",
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         description="Slug del destino",
     *         required=true,
     *         @OA\Schema(type="string", example="balneario-el-tephe")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Resumen de reseñas obtenido exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="destino", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="nombre", type="string", example="Balneario El Tephé"),
     *                     @OA\Property(property="slug", type="string", example="balneario-el-tephe")
     *                 ),
     *                 @OA\Property(property="summary", type="object",
     *                     @OA\Property(property="total_reviews", type="integer", example=45),
     *                     @OA\Property(property="average_rating", type="number", format="float", example=4.2),
     *                     @OA\Property(property="rating_distribution", type="array", @OA\Items(
     *                         @OA\Property(property="rating", type="integer", example=5),
     *                         @OA\Property(property="count", type="integer", example=25),
     *                         @OA\Property(property="percentage", type="number", format="float", example=55.6)
     *                     )),
     *                     @OA\Property(property="recent_trend", type="object",
     *                         @OA\Property(property="last_month_avg", type="number", format="float", example=4.3),
     *                         @OA\Property(property="trend_direction", type="string", example="up"),
     *                         @OA\Property(property="trend_percentage", type="number", format="float", example=2.4)
     *                     ),
     *                     @OA\Property(property="top_keywords", type="array", @OA\Items(
     *                         @OA\Property(property="keyword", type="string", example="excelente"),
     *                         @OA\Property(property="count", type="integer", example=12),
     *                         @OA\Property(property="sentiment", type="string", example="positive")
     *                     )),
     *                     @OA\Property(property="review_sentiment", type="object",
     *                         @OA\Property(property="positive", type="integer", example=35),
     *                         @OA\Property(property="neutral", type="integer", example=8),
     *                         @OA\Property(property="negative", type="integer", example=2)
     *                     )
     *                 ),
     *                 @OA\Property(property="recent_reviews", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="rating", type="integer", example=5),
     *                     @OA\Property(property="comment", type="string", example="Excelente lugar para la familia"),
     *                     @OA\Property(property="user_name", type="string", example="Juan Pérez"),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="has_reply", type="boolean", example=true)
     *                 ))
     *             ),
     *             @OA\Property(property="message", type="string", example="Resumen de reseñas obtenido exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Destino no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Destino no encontrado.")
     *         )
     *     )
     * )
     */
    public function getReviewsSummary(string $slug): JsonResponse
    {
        try {
            // Buscar el destino por slug
            $destino = Destino::where('slug', $slug)
                ->where('status', 'published')
                ->first();

            if (!$destino) {
                return $this->sendError('Destino no encontrado.', [], 404);
            }

            return $this->getCachedData("destino_reviews_summary_{$slug}", function () use ($destino) {
                return $this->generateReviewsSummary($destino);
            }, 600); // Cache por 10 minutos

        } catch (\Exception $e) {
            Log::error('Error getting reviews summary: ' . $e->getMessage());
            return $this->sendError('Error al obtener resumen de reseñas: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/public/reviews/{id}/report",
     *     operationId="reportReview",
     *     tags={"Public Content"},
     *     summary="Reportar una reseña",
     *     description="Permite a los usuarios reportar reseñas inapropiadas o incorrectas",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la reseña",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"reason"},
     *             @OA\Property(property="reason", type="string", example="inappropriate_content", description="Razón del reporte"),
     *             @OA\Property(property="description", type="string", example="Contenido ofensivo", description="Descripción adicional del reporte"),
     *             @OA\Property(property="user_email", type="string", example="user@example.com", description="Email del usuario que reporta")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reporte enviado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="report_id", type="string", example="rep_123456789"),
     *                 @OA\Property(property="status", type="string", example="pending_review"),
     *                 @OA\Property(property="estimated_response_time", type="string", example="24-48 hours")
     *             ),
     *             @OA\Property(property="message", type="string", example="Reporte enviado exitosamente. Será revisado en 24-48 horas.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Reseña no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Reseña no encontrada.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Los datos proporcionados no son válidos."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function reportReview(Request $request, int $id): JsonResponse
    {
        try {
            // Validar datos
            $validated = $request->validate([
                'reason' => 'required|string|in:inappropriate_content,spam,fake_review,offensive_language,other',
                'description' => 'nullable|string|max:500',
                'user_email' => 'nullable|email'
            ]);

            // Buscar la reseña
            $review = Review::where('id', $id)
                ->where('status', 'approved')
                ->first();

            if (!$review) {
                return $this->sendError('Reseña no encontrada.', [], 404);
            }

            // Crear reporte (simulado por ahora)
            $reportId = 'rep_' . uniqid();
            
            // En producción, aquí se guardaría en una tabla de reportes
            Log::info('Review reported', [
                'review_id' => $id,
                'report_id' => $reportId,
                'reason' => $validated['reason'],
                'description' => $validated['description'] ?? null,
                'user_email' => $validated['user_email'] ?? null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            $data = [
                'report_id' => $reportId,
                'status' => 'pending_review',
                'estimated_response_time' => '24-48 hours'
            ];

            return $this->sendResponse($data, 'Reporte enviado exitosamente. Será revisado en 24-48 horas.');

        } catch (\Exception $e) {
            Log::error('Error reporting review: ' . $e->getMessage());
            return $this->sendError('Error al reportar reseña: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/public/reviews/{id}/reply",
     *     operationId="replyToReview",
     *     tags={"Public Content"},
     *     summary="Responder a una reseña",
     *     description="Permite al dueño del destino responder a una reseña",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la reseña",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"reply_text"},
     *             @OA\Property(property="reply_text", type="string", example="Gracias por su comentario. Nos alegra que haya disfrutado su visita.", description="Texto de la respuesta"),
     *             @OA\Property(property="is_public", type="boolean", example=true, description="Si la respuesta es pública o privada")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Respuesta enviada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="reply", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="review_id", type="integer", example=1),
     *                     @OA\Property(property="reply_text", type="string", example="Gracias por su comentario..."),
     *                     @OA\Property(property="is_public", type="boolean", example=true),
     *                     @OA\Property(property="provider_name", type="string", example="Balneario El Tephé"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Respuesta enviada exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Solo el dueño del destino puede responder a reseñas.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Reseña no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Reseña no encontrada.")
     *         )
     *     )
     * )
     */
    public function replyToReview(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            // Verificar que el usuario esté autenticado y sea proveedor
            if (!$user || !$user->isProvider()) {
                return $this->sendError('Solo los proveedores pueden responder a reseñas.', [], 403);
            }

            // Validar datos
            $validated = $request->validate([
                'reply_text' => 'required|string|max:1000',
                'is_public' => 'boolean'
            ]);

            // Buscar la reseña
            $review = Review::where('id', $id)
                ->where('status', 'approved')
                ->with('destino')
                ->first();

            if (!$review) {
                return $this->sendError('Reseña no encontrada.', [], 404);
            }

            // Verificar que el usuario sea el dueño del destino
            if ($review->destino->user_id !== $user->id) {
                return $this->sendError('Solo el dueño del destino puede responder a reseñas.', [], 403);
            }

            // Verificar que no haya una respuesta previa
            if ($review->reply) {
                return $this->sendError('Ya existe una respuesta para esta reseña.', [], 422);
            }

            // Crear la respuesta (simulado por ahora)
            // En producción, esto se guardaría en una tabla de respuestas
            $reply = [
                'id' => rand(1000, 9999),
                'review_id' => $id,
                'reply_text' => $validated['reply_text'],
                'is_public' => $validated['is_public'] ?? true,
                'provider_name' => $review->destino->name,
                'created_at' => now()
            ];

            // Log de la respuesta
            Log::info('Review reply created', [
                'review_id' => $id,
                'destino_id' => $review->destino->id,
                'provider_id' => $user->id,
                'reply_text' => $validated['reply_text'],
                'is_public' => $validated['is_public'] ?? true
            ]);

            return $this->sendResponse(['reply' => $reply], 'Respuesta enviada exitosamente.');

        } catch (\Exception $e) {
            Log::error('Error replying to review: ' . $e->getMessage());
            return $this->sendError('Error al responder a la reseña: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Generar resumen de reseñas
     */
    private function generateReviewsSummary(Destino $destino): JsonResponse
    {
        // Datos básicos del destino
        $destinoData = [
            'id' => $destino->id,
            'nombre' => $destino->name,
            'slug' => $destino->slug
        ];

        // Estadísticas generales
        $totalReviews = $destino->reviews_count ?? 0;
        $averageRating = $destino->average_rating ?? 0;

        // Distribución de calificaciones
        $ratingDistribution = $this->generateRatingDistribution($destino->id);

        // Tendencias recientes (simulado)
        $recentTrend = [
            'last_month_avg' => $averageRating + (rand(-20, 20) / 100),
            'trend_direction' => rand(0, 1) ? 'up' : 'down',
            'trend_percentage' => rand(10, 50) / 10
        ];

        // Palabras clave más usadas (simulado)
        $topKeywords = [
            ['keyword' => 'excelente', 'count' => rand(8, 15), 'sentiment' => 'positive'],
            ['keyword' => 'bonito', 'count' => rand(5, 12), 'sentiment' => 'positive'],
            ['keyword' => 'familiar', 'count' => rand(4, 10), 'sentiment' => 'positive'],
            ['keyword' => 'limpio', 'count' => rand(3, 8), 'sentiment' => 'positive'],
            ['keyword' => 'accesible', 'count' => rand(2, 6), 'sentiment' => 'positive']
        ];

        // Análisis de sentimiento (simulado)
        $reviewSentiment = [
            'positive' => (int)($totalReviews * 0.8),
            'neutral' => (int)($totalReviews * 0.15),
            'negative' => (int)($totalReviews * 0.05)
        ];

        // Reseñas recientes
        $recentReviews = $destino->reviews()
            ->with('user:id,name')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'user_name' => $review->user->name,
                    'created_at' => $review->created_at,
                    'has_reply' => false // Simulado por ahora
                ];
            });

        $summary = [
            'total_reviews' => $totalReviews,
            'average_rating' => $averageRating,
            'rating_distribution' => $ratingDistribution,
            'recent_trend' => $recentTrend,
            'top_keywords' => $topKeywords,
            'review_sentiment' => $reviewSentiment
        ];

        $data = [
            'destino' => $destinoData,
            'summary' => $summary,
            'recent_reviews' => $recentReviews
        ];

        return $this->sendResponse($data, 'Resumen de reseñas obtenido exitosamente.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/public/destinos/{slug}/actividades",
     *     operationId="getDestinoActividades",
     *     tags={"Public Content"},
     *     summary="Obtener actividades de un destino",
     *     description="Retorna las actividades disponibles en un destino específico",
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         description="Slug del destino",
     *         required=true,
     *         @OA\Schema(type="string", example="balneario-el-tephe")
     *     ),
     *     @OA\Parameter(
     *         name="difficulty",
     *         in="query",
     *         description="Filtrar por nivel de dificultad",
     *         required=false,
     *         @OA\Schema(type="string", enum={"facil", "moderado", "dificil", "experto"}, example="moderado")
     *     ),
     *     @OA\Parameter(
     *         name="price_min",
     *         in="query",
     *         description="Precio mínimo",
     *         required=false,
     *         @OA\Schema(type="number", format="float", example=0.00)
     *     ),
     *     @OA\Parameter(
     *         name="price_max",
     *         in="query",
     *         description="Precio máximo",
     *         required=false,
     *         @OA\Schema(type="number", format="float", example=500.00)
     *     ),
     *     @OA\Parameter(
     *         name="duration_min",
     *         in="query",
     *         description="Duración mínima en minutos",
     *         required=false,
     *         @OA\Schema(type="integer", example=30)
     *     ),
     *     @OA\Parameter(
     *         name="duration_max",
     *         in="query",
     *         description="Duración máxima en minutos",
     *         required=false,
     *         @OA\Schema(type="integer", example=240)
     *     ),
     *     @OA\Parameter(
     *         name="is_featured",
     *         in="query",
     *         description="Solo actividades destacadas",
     *         required=false,
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Campo de ordenamiento",
     *         required=false,
     *         @OA\Schema(type="string", enum={"name", "price", "duration_minutes", "difficulty_level"}, example="price")
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Orden de clasificación",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, example="asc")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número de página",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Elementos por página",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Actividades obtenidas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="destino", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Balneario El Tephé"),
     *                     @OA\Property(property="slug", type="string", example="balneario-el-tephe")
     *                 ),
     *                 @OA\Property(property="actividades", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Recorrido en Lancha"),
     *                     @OA\Property(property="slug", type="string", example="recorrido-lancha"),
     *                     @OA\Property(property="short_description", type="string", example="Explora el lago en lancha"),
     *                     @OA\Property(property="duration_minutes", type="integer", example=90),
     *                     @OA\Property(property="duration_formatted", type="string", example="1h 30m"),
     *                     @OA\Property(property="price", type="number", format="float", example=150.00),
     *                     @OA\Property(property="price_formatted", type="string", example="MXN 150.00"),
     *                     @OA\Property(property="currency", type="string", example="MXN"),
     *                     @OA\Property(property="difficulty_level", type="string", example="facil"),
     *                     @OA\Property(property="age_range_formatted", type="string", example="8-65 años"),
     *                     @OA\Property(property="participant_range_formatted", type="string", example="2-8 personas"),
     *                     @OA\Property(property="is_featured", type="boolean", example=true),
     *                     @OA\Property(property="main_image", type="string", example="https://example.com/image.jpg"),
     *                     @OA\Property(property="weather_dependent", type="boolean", example=false),
     *                     @OA\Property(property="meeting_point", type="string", example="Muelle principal"),
     *                     @OA\Property(property="meeting_time", type="string", example="09:00:00"),
     *                     @OA\Property(property="included_items", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="what_to_bring", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="safety_notes", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="cancellation_policy", type="string", example="Cancelación gratuita hasta 24h antes"),
     *                     @OA\Property(property="is_currently_available", type="boolean", example=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )),
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="per_page", type="integer", example=15),
     *                     @OA\Property(property="total", type="integer", example=8),
     *                     @OA\Property(property="last_page", type="integer", example=1)
     *                 ),
     *                 @OA\Property(property="stats", type="object",
     *                     @OA\Property(property="total_actividades", type="integer", example=8),
     *                     @OA\Property(property="featured_actividades", type="integer", example=3),
     *                     @OA\Property(property="price_range", type="object",
     *                         @OA\Property(property="min", type="number", format="float", example=50.00),
     *                         @OA\Property(property="max", type="number", format="float", example=300.00)
     *                     ),
     *                     @OA\Property(property="duration_range", type="object",
     *                         @OA\Property(property="min", type="integer", example=30),
     *                         @OA\Property(property="max", type="integer", example=240)
     *                     ),
     *                     @OA\Property(property="difficulty_distribution", type="object",
     *                         @OA\Property(property="facil", type="integer", example=3),
     *                         @OA\Property(property="moderado", type="integer", example=4),
     *                         @OA\Property(property="dificil", type="integer", example=1),
     *                         @OA\Property(property="experto", type="integer", example=0)
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Actividades obtenidas exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Destino no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Destino no encontrado.")
     *         )
     *     )
     * )
     */
    public function getActividades(Request $request, string $slug): JsonResponse
    {
        try {
            // Validar parámetros
            $validated = $request->validate([
                'difficulty' => 'nullable|string|in:facil,moderado,dificil,experto',
                'price_min' => 'nullable|numeric|min:0',
                'price_max' => 'nullable|numeric|min:0|gte:price_min',
                'duration_min' => 'nullable|integer|min:0',
                'duration_max' => 'nullable|integer|min:0|gte:duration_min',
                'is_featured' => 'nullable|boolean',
                'sort_by' => 'nullable|string|in:name,price,duration_minutes,difficulty_level',
                'sort_order' => 'nullable|string|in:asc,desc',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            // Buscar el destino
            $destino = Destino::where('slug', $slug)
                ->where('status', 'published')
                ->first();

            if (!$destino) {
                return $this->sendError('Destino no encontrado.', [], 404);
            }

            // Crear clave de cache
            $cacheKey = "destino_actividades_{$slug}_" . md5(serialize($validated));

            return $this->getCachedData($cacheKey, function () use ($destino, $validated) {
                return $this->getActividadesList($destino, $validated);
            }, 300); // Cache por 5 minutos

        } catch (\Exception $e) {
            Log::error('Error getting actividades: ' . $e->getMessage());
            return $this->sendError('Error al obtener actividades: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Obtener lista de actividades con filtros
     */
    private function getActividadesList(Destino $destino, array $filters): JsonResponse
    {
        $query = Actividad::with([
            'categorias:id,name',
            'caracteristicas:id,name',
            'tags:id,name'
        ])
        ->where('destino_id', $destino->id)
        ->where('is_available', true);

        // Filtro por dificultad
        if (!empty($filters['difficulty'])) {
            $query->byDifficulty($filters['difficulty']);
        }

        // Filtro por precio
        if (!empty($filters['price_min']) || !empty($filters['price_max'])) {
            $min = $filters['price_min'] ?? 0;
            $max = $filters['price_max'] ?? 999999;
            $query->byPriceRange($min, $max);
        }

        // Filtro por duración
        if (!empty($filters['duration_min']) || !empty($filters['duration_max'])) {
            $min = $filters['duration_min'] ?? 0;
            $max = $filters['duration_max'] ?? 999999;
            $query->byDurationRange($min, $max);
        }

        // Filtro por destacadas
        if (isset($filters['is_featured'])) {
            $query->where('is_featured', $filters['is_featured']);
        }

        // Ordenamiento
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $filters['per_page'] ?? 15;
        $actividades = $query->paginate($perPage);

        // Transformar resultados
        $actividadesData = $actividades->getCollection()->map(function ($actividad) {
            return $this->transformActividad($actividad);
        });

        // Estadísticas
        $stats = $this->generateActividadesStats($destino->id);

        $data = [
            'destino' => [
                'id' => $destino->id,
                'name' => $destino->name,
                'slug' => $destino->slug
            ],
            'actividades' => $actividadesData,
            'pagination' => [
                'current_page' => $actividades->currentPage(),
                'per_page' => $actividades->perPage(),
                'total' => $actividades->total(),
                'last_page' => $actividades->lastPage()
            ],
            'stats' => $stats
        ];

        return $this->sendResponse($data, 'Actividades obtenidas exitosamente.');
    }

    /**
     * Generar estadísticas de actividades
     */
    private function generateActividadesStats(int $destinoId): array
    {
        $actividades = Actividad::where('destino_id', $destinoId)
            ->where('is_available', true)
            ->get();

        $priceRange = [
            'min' => $actividades->min('price') ?? 0,
            'max' => $actividades->max('price') ?? 0
        ];

        $durationRange = [
            'min' => $actividades->min('duration_minutes') ?? 0,
            'max' => $actividades->max('duration_minutes') ?? 0
        ];

        $difficultyDistribution = [
            'facil' => $actividades->where('difficulty_level', 'facil')->count(),
            'moderado' => $actividades->where('difficulty_level', 'moderado')->count(),
            'dificil' => $actividades->where('difficulty_level', 'dificil')->count(),
            'experto' => $actividades->where('difficulty_level', 'experto')->count()
        ];

        return [
            'total_actividades' => $actividades->count(),
            'featured_actividades' => $actividades->where('is_featured', true)->count(),
            'price_range' => $priceRange,
            'duration_range' => $durationRange,
            'difficulty_distribution' => $difficultyDistribution
        ];
    }

    /**
     * Transformar actividad para respuesta
     */
    private function transformActividad(Actividad $actividad): array
    {
        return [
            'id' => $actividad->id,
            'name' => $actividad->name,
            'slug' => $actividad->slug,
            'short_description' => $actividad->short_description,
            'duration_minutes' => $actividad->duration_minutes,
            'duration_formatted' => $actividad->duration_formatted,
            'price' => $actividad->price,
            'price_formatted' => $actividad->price_formatted,
            'currency' => $actividad->currency,
            'difficulty_level' => $actividad->difficulty_level,
            'age_range_formatted' => $actividad->age_range_formatted,
            'participant_range_formatted' => $actividad->participant_range_formatted,
            'is_featured' => $actividad->is_featured,
            'main_image' => $actividad->main_image,
            'weather_dependent' => $actividad->weather_dependent,
            'meeting_point' => $actividad->meeting_point,
            'meeting_time' => $actividad->meeting_time,
            'included_items' => $actividad->included_items ?? [],
            'what_to_bring' => $actividad->what_to_bring ?? [],
            'safety_notes' => $actividad->safety_notes ?? [],
            'cancellation_policy' => $actividad->cancellation_policy,
            'is_currently_available' => $actividad->isCurrentlyAvailable(),
            'created_at' => $actividad->created_at
        ];
    }
} 