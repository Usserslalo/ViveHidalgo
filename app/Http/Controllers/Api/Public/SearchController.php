<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\BaseController;
use App\Models\Destino;
use App\Models\Region;
use App\Models\Categoria;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SearchController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/v1/public/search/autocomplete",
     *     operationId="searchAutocomplete",
     *     tags={"Public Search"},
     *     summary="Autocompletado de búsqueda de destinos",
     *     description="Sugerencias en tiempo real para el buscador principal. Retorna destinos que coincidan con el término de búsqueda.",
     *     @OA\Parameter(
     *         name="q",
     *         in="query",
     *         required=true,
     *         description="Término de búsqueda (mínimo 2 caracteres)",
     *         @OA\Schema(type="string", minLength=2)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Autocompletado exitoso",
     *         @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="array", 
     *                  @OA\Items(type="object",
     *                      @OA\Property(property="id", type="integer", example=1),
     *                      @OA\Property(property="titulo", type="string", example="Balneario El Tephé"),
     *                      @OA\Property(property="slug", type="string", example="balneario-el-tephe"),
     *                      @OA\Property(property="region", type="string", example="Valle del Mezquital"),
     *                      @OA\Property(property="categoria", type="string", example="Balneario"),
     *                      @OA\Property(property="imagen_principal", type="string", nullable=true, example="https://cdn...")
     *                  )
     *              ),
     *              @OA\Property(property="message", type="string", example="Sugerencias de autocompletado recuperadas exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Solicitud incorrecta (query muy corto)",
     *         @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=false),
     *              @OA\Property(property="message", type="string", example="El término de búsqueda debe tener al menos 2 caracteres.")
     *         )
     *     )
     * )
     */
    public function autocomplete(Request $request): JsonResponse
    {
        // Validación del request
        $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $query = trim($request->input('q'));

        // Cache key único para esta búsqueda
        $cacheKey = "autocomplete_destinos_{$query}";
        
        return $this->getCachedData($cacheKey, function () use ($query) {
            return $this->performAutocompleteSearch($query);
        }, 300); // Cache por 5 minutos
    }

    /**
     * @OA\Get(
     *     path="/api/v1/home/filters",
     *     operationId="getHomeFilters",
     *     tags={"Public Home"},
     *     summary="Obtener filtros optimizados para frontend",
     *     description="Retorna estructura completa para filtros: categorías, características, regiones, y price_ranges con conteos y emojis.",
     *     @OA\Response(
     *         response=200,
     *         description="Filtros recuperados exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="filters", type="object",
     *                     @OA\Property(property="categorias", type="array", @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Pueblo Mágico"),
     *                         @OA\Property(property="count", type="integer", example=8),
     *                         @OA\Property(property="icon", type="string", example="🏘️")
     *                     )),
     *                     @OA\Property(property="caracteristicas", type="array", @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Gastronomía"),
     *                         @OA\Property(property="count", type="integer", example=15),
     *                         @OA\Property(property="icon", type="string", example="🍽️")
     *                     )),
     *                     @OA\Property(property="regiones", type="array", @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Comarca Minera"),
     *                         @OA\Property(property="count", type="integer", example=6)
     *                     )),
     *                     @OA\Property(property="price_ranges", type="array", @OA\Items(
     *                         @OA\Property(property="value", type="string", example="gratis"),
     *                         @OA\Property(property="label", type="string", example="Gratis"),
     *                         @OA\Property(property="count", type="integer", example=5)
     *                     ))
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Filtros recuperados exitosamente.")
     *         )
     *     )
     * )
     */
    public function filters(): \Illuminate\Http\JsonResponse
    {
        $data = Cache::remember('home_filters_data', 600, function () {
            // Categorías con conteos y emojis
            $categorias = \App\Models\Categoria::withCount(['destinos as count' => function ($query) {
                    $query->where('status', 'published');
                }])
                ->whereHas('destinos', function ($query) {
                    $query->where('status', 'published');
                })
                ->get()
                ->map(function ($categoria) {
                    return [
                        'id' => $categoria->id,
                        'name' => $categoria->name,
                        'count' => $categoria->count,
                        'icon' => $this->getCategoriaIcon($categoria->name)
                    ];
                })
                ->sortByDesc('count')
                ->values();

            // Características con conteos y emojis
            $caracteristicas = \App\Models\Caracteristica::withCount(['destinos as count' => function ($query) {
                    $query->where('status', 'published');
                }])
                ->whereHas('destinos', function ($query) {
                    $query->where('status', 'published');
                })
                ->activas()
                ->get()
                ->map(function ($caracteristica) {
                    return [
                        'id' => $caracteristica->id,
                        'name' => $caracteristica->nombre,
                        'count' => $caracteristica->count,
                        'icon' => $this->getCaracteristicaIcon($caracteristica->nombre)
                    ];
                })
                ->sortByDesc('count')
                ->values();

            // Regiones con conteos
            $regiones = \App\Models\Region::withCount(['destinos as count' => function ($query) {
                    $query->where('status', 'published');
                }])
                ->whereHas('destinos', function ($query) {
                    $query->where('status', 'published');
                })
                ->get()
                ->map(function ($region) {
                    return [
                        'id' => $region->id,
                        'name' => $region->name,
                        'count' => $region->count
                    ];
                })
                ->sortByDesc('count')
                ->values();

            // Rangos de precio con conteos
            $priceRanges = [
                ['value' => 'gratis', 'label' => 'Gratis', 'count' => 0],
                ['value' => 'economico', 'label' => 'Económico', 'count' => 0],
                ['value' => 'moderado', 'label' => 'Moderado', 'count' => 0],
                ['value' => 'premium', 'label' => 'Premium', 'count' => 0],
                ['value' => 'lujo', 'label' => 'Lujo', 'count' => 0]
            ];

            // Contar destinos por rango de precio
            $priceCounts = \App\Models\Destino::where('status', 'published')
                ->selectRaw('price_range, COUNT(*) as count')
                ->groupBy('price_range')
                ->pluck('count', 'price_range')
                ->toArray();

            foreach ($priceRanges as &$range) {
                $range['count'] = $priceCounts[$range['value']] ?? 0;
            }

            return [
                'filters' => [
                    'categorias' => $categorias,
                    'caracteristicas' => $caracteristicas,
                    'regiones' => $regiones,
                    'price_ranges' => $priceRanges
                ]
            ];
        });

        return $this->successResponse($data, 'Filtros recuperados exitosamente.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/public/search/advanced",
     *     operationId="advancedSearch",
     *     tags={"Public Content"},
     *     summary="Búsqueda avanzada de destinos",
     *     description="Búsqueda con múltiples criterios combinados: texto, categorías, características, precio, rating, distancia",
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         description="Término de búsqueda de texto",
     *         required=false,
     *         @OA\Schema(type="string", example="balneario")
     *     ),
     *     @OA\Parameter(
     *         name="categorias",
     *         in="query",
     *         description="IDs de categorías (separados por coma)",
     *         required=false,
     *         @OA\Schema(type="string", example="1,2,3")
     *     ),
     *     @OA\Parameter(
     *         name="caracteristicas",
     *         in="query",
     *         description="IDs de características (separados por coma)",
     *         required=false,
     *         @OA\Schema(type="string", example="1,5,8")
     *     ),
     *     @OA\Parameter(
     *         name="precio_min",
     *         in="query",
     *         description="Precio mínimo",
     *         required=false,
     *         @OA\Schema(type="number", format="float", example=100.00)
     *     ),
     *     @OA\Parameter(
     *         name="precio_max",
     *         in="query",
     *         description="Precio máximo",
     *         required=false,
     *         @OA\Schema(type="number", format="float", example=500.00)
     *     ),
     *     @OA\Parameter(
     *         name="rating_min",
     *         in="query",
     *         description="Rating mínimo (1-5)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=5, example=4)
     *     ),
     *     @OA\Parameter(
     *         name="lat",
     *         in="query",
     *         description="Latitud para búsqueda por distancia",
     *         required=false,
     *         @OA\Schema(type="number", format="float", example=20.1234)
     *     ),
     *     @OA\Parameter(
     *         name="lng",
     *         in="query",
     *         description="Longitud para búsqueda por distancia",
     *         required=false,
     *         @OA\Schema(type="number", format="float", example=-98.5678)
     *     ),
     *     @OA\Parameter(
     *         name="distancia_max",
     *         in="query",
     *         description="Distancia máxima en kilómetros",
     *         required=false,
     *         @OA\Schema(type="number", format="float", example=50.0)
     *     ),
     *     @OA\Parameter(
     *         name="regiones",
     *         in="query",
     *         description="IDs de regiones (separados por coma)",
     *         required=false,
     *         @OA\Schema(type="string", example="1,3")
     *     ),
     *     @OA\Parameter(
     *         name="tags",
     *         in="query",
     *         description="IDs de tags (separados por coma)",
     *         required=false,
     *         @OA\Schema(type="string", example="1,4,7")
     *     ),
     *     @OA\Parameter(
     *         name="is_top",
     *         in="query",
     *         description="Solo destinos top",
     *         required=false,
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Campo de ordenamiento",
     *         required=false,
     *         @OA\Schema(type="string", enum={"name", "rating", "price", "distance", "created_at"}, example="rating")
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Orden de clasificación",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, example="desc")
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
     *         description="Búsqueda avanzada realizada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="destinos", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Balneario El Tephé"),
     *                     @OA\Property(property="slug", type="string", example="balneario-el-tephe"),
     *                     @OA\Property(property="description", type="string", example="Hermoso balneario..."),
     *                     @OA\Property(property="price", type="number", format="float", example=150.00),
     *                     @OA\Property(property="rating", type="number", format="float", example=4.5),
     *                     @OA\Property(property="reviews_count", type="integer", example=45),
     *                     @OA\Property(property="main_image", type="string", example="https://example.com/image.jpg"),
     *                     @OA\Property(property="region", type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Pachuca")
     *                     ),
     *                     @OA\Property(property="categorias", type="array", @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Balnearios")
     *                     )),
     *                     @OA\Property(property="caracteristicas", type="array", @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Alberca")
     *                     )),
     *                     @OA\Property(property="distance", type="number", format="float", example=12.5),
     *                     @OA\Property(property="is_top", type="boolean", example=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )),
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="per_page", type="integer", example=15),
     *                     @OA\Property(property="total", type="integer", example=45),
     *                     @OA\Property(property="last_page", type="integer", example=3)
     *                 ),
     *                 @OA\Property(property="filters_applied", type="object",
     *                     @OA\Property(property="query", type="string", example="balneario"),
     *                     @OA\Property(property="categorias_count", type="integer", example=2),
     *                     @OA\Property(property="caracteristicas_count", type="integer", example=3),
     *                     @OA\Property(property="price_range", type="object",
     *                         @OA\Property(property="min", type="number", format="float", example=100.00),
     *                         @OA\Property(property="max", type="number", format="float", example=500.00)
     *                     ),
     *                     @OA\Property(property="rating_min", type="integer", example=4),
     *                     @OA\Property(property="distance_max", type="number", format="float", example=50.0)
     *                 ),
     *                 @OA\Property(property="search_stats", type="object",
     *                     @OA\Property(property="total_results", type="integer", example=45),
     *                     @OA\Property(property="search_time_ms", type="integer", example=125),
     *                     @OA\Property(property="cache_hit", type="boolean", example=false)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Búsqueda avanzada realizada exitosamente.")
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
    public function advancedSearch(Request $request): JsonResponse
    {
        try {
            // Validar parámetros
            $validated = $request->validate([
                'query' => 'nullable|string|max:100',
                'categorias' => 'nullable|string',
                'caracteristicas' => 'nullable|string',
                'precio_min' => 'nullable|numeric|min:0',
                'precio_max' => 'nullable|numeric|min:0|gte:precio_min',
                'rating_min' => 'nullable|integer|min:1|max:5',
                'lat' => 'nullable|numeric|between:-90,90',
                'lng' => 'nullable|numeric|between:-180,180',
                'distancia_max' => 'nullable|numeric|min:0|max:1000',
                'regiones' => 'nullable|string',
                'tags' => 'nullable|string',
                'is_top' => 'nullable|boolean',
                'sort_by' => 'nullable|string|in:name,rating,price,distance,created_at',
                'sort_order' => 'nullable|string|in:asc,desc',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            // Crear clave de cache basada en parámetros
            $cacheKey = 'advanced_search_' . md5(serialize($validated));

            return $this->getCachedData($cacheKey, function () use ($validated) {
                return $this->performAdvancedSearch($validated);
            }, 300); // Cache por 5 minutos

        } catch (\Exception $e) {
            Log::error('Error in advanced search: ' . $e->getMessage());
            return $this->sendError('Error en búsqueda avanzada: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Realizar la búsqueda de autocompletado
     */
    private function performAutocompleteSearch(string $query): JsonResponse
    {
        $searchTerm = "%{$query}%";

        // Búsqueda de destinos con múltiples criterios
        $destinos = Destino::where('status', 'published')
            ->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('slug', 'like', $searchTerm)
                  ->orWhere('short_description', 'like', $searchTerm)
                  ->orWhere('descripcion_corta', 'like', $searchTerm)
                  ->orWhereHas('tags', function ($tagQuery) use ($searchTerm) {
                      $tagQuery->where('name', 'like', $searchTerm);
                  })
                  ->orWhereHas('region', function ($regionQuery) use ($searchTerm) {
                      $regionQuery->where('name', 'like', $searchTerm);
                  })
                  ->orWhereHas('categorias', function ($categoriaQuery) use ($searchTerm) {
                      $categoriaQuery->where('name', 'like', $searchTerm);
                  });
            })
            ->with([
                'region:id,name',
                'categorias:id,name',
                'imagenes' => function ($q) { 
                    $q->where('is_main', true)->orWhere('order', 1); 
                }
            ])
            ->orderByRaw("
                CASE 
                    WHEN name LIKE ? THEN 1
                    WHEN name LIKE ? THEN 2
                    ELSE 3
                END
            ", ["{$query}%", "%{$query}%"])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($destino) {
                return [
                    'id' => $destino->id,
                    'titulo' => $destino->name,
                    'slug' => $destino->slug,
                    'region' => $destino->region ? $destino->region->name : null,
                    'categoria' => $destino->categorias->first() ? $destino->categorias->first()->name : null,
                    'imagen_principal' => $destino->imagenes->first() ? $destino->imagenes->first()->url : null,
                ];
            });

        return $this->successResponse($destinos, 'Sugerencias de autocompletado recuperadas exitosamente.');
    }

    /**
     * Obtener emoji para categoría
     */
    private function getCategoriaIcon(string $categoriaName): string
    {
        $icons = [
            'Pueblo Mágico' => '🏘️',
            'Balneario' => '🏊‍♂️',
            'Aventura' => '🏔️',
            'Cultura' => '🏛️',
            'Gastronomía' => '🍽️',
            'Naturaleza' => '🌲',
            'Historia' => '📚',
            'Religión' => '⛪',
            'Arte' => '🎨',
            'Música' => '🎵',
            'Festival' => '🎉',
            'Deportes' => '⚽',
            'Educación' => '🎓',
            'Comercio' => '🛍️',
            'Transporte' => '🚗'
        ];

        return $icons[$categoriaName] ?? '📍';
    }

    /**
     * Obtener emoji para característica
     */
    private function getCaracteristicaIcon(string $caracteristicaName): string
    {
        $icons = [
            'Gastronomía' => '🍽️',
            'Estacionamiento' => '🅿️',
            'WiFi' => '📶',
            'Aire acondicionado' => '❄️',
            'Calefacción' => '🔥',
            'Restaurante' => '🍴',
            'Bar' => '🍺',
            'Cafetería' => '☕',
            'Piscina' => '🏊‍♂️',
            'Gimnasio' => '💪',
            'Spa' => '💆‍♀️',
            'Salón de eventos' => '🎪',
            'Servicio de habitaciones' => '🛎️',
            'Recepción 24h' => '🕐',
            'Mascotas permitidas' => '🐕',
            'Accesible' => '♿',
            'Fumadores' => '🚬',
            'No fumadores' => '🚭',
            'Vista panorámica' => '🏞️',
            'Terraza' => '🌅',
            'Jardín' => '🌺',
            'Aventura' => '🏔️',
            'Deportes extremos' => '🧗‍♂️',
            'Senderismo' => '🥾',
            'Escalada' => '🧗‍♀️',
            'Historia' => '📚',
            'Arquitectura' => '🏛️',
            'Museos' => '🏛️',
            'Iglesias' => '⛪',
            'Naturaleza' => '🌲',
            'Parques' => '🌳',
            'Cascadas' => '🌊',
            'Montañas' => '⛰️',
            'Ríos' => '🏞️',
            'Lagos' => '🏞️',
            'Cuevas' => '🕳️',
            'Flora' => '🌸',
            'Fauna' => '🦋'
        ];

        return $icons[$caracteristicaName] ?? '📍';
    }

    /**
     * Realizar búsqueda avanzada
     */
    private function performAdvancedSearch(array $filters): JsonResponse
    {
        $startTime = microtime(true);

        // Construir query base
        $query = Destino::with([
            'region:id,name',
            'categorias:id,name',
            'caracteristicas:id,name',
            'tags:id,name',
            'imagenes' => function ($q) {
                $q->where('is_main', true)->limit(1);
            }
        ])
        ->where('status', 'published');

        // Filtro de texto
        if (!empty($filters['query'])) {
            $searchTerm = $filters['query'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('short_description', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Filtro de categorías
        if (!empty($filters['categorias'])) {
            $categoriaIds = explode(',', $filters['categorias']);
            $query->whereHas('categorias', function ($q) use ($categoriaIds) {
                $q->whereIn('categorias.id', $categoriaIds);
            });
        }

        // Filtro de características
        if (!empty($filters['caracteristicas'])) {
            $caracteristicaIds = explode(',', $filters['caracteristicas']);
            $query->whereHas('caracteristicas', function ($q) use ($caracteristicaIds) {
                $q->whereIn('caracteristicas.id', $caracteristicaIds);
            });
        }

        // Filtro de regiones
        if (!empty($filters['regiones'])) {
            $regionIds = explode(',', $filters['regiones']);
            $query->whereIn('region_id', $regionIds);
        }

        // Filtro de tags
        if (!empty($filters['tags'])) {
            $tagIds = explode(',', $filters['tags']);
            $query->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            });
        }

        // Filtro de precio
        if (!empty($filters['precio_min'])) {
            $query->where('price', '>=', $filters['precio_min']);
        }
        if (!empty($filters['precio_max'])) {
            $query->where('price', '<=', $filters['precio_max']);
        }

        // Filtro de rating
        if (!empty($filters['rating_min'])) {
            $query->where('average_rating', '>=', $filters['rating_min']);
        }

        // Filtro de destinos top
        if (isset($filters['is_top'])) {
            $query->where('is_top', $filters['is_top']);
        }

        // Búsqueda por distancia
        if (!empty($filters['lat']) && !empty($filters['lng']) && !empty($filters['distancia_max'])) {
            $lat = $filters['lat'];
            $lng = $filters['lng'];
            $radius = $filters['distancia_max'];

            $query->selectRaw("
                *,
                (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
            ", [$lat, $lng, $lat])
            ->having('distance', '<=', $radius)
            ->orderBy('distance');
        }

        // Ordenamiento
        $sortBy = $filters['sort_by'] ?? 'rating';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        switch ($sortBy) {
            case 'name':
                $query->orderBy('name', $sortOrder);
                break;
            case 'price':
                $query->orderBy('price', $sortOrder);
                break;
            case 'created_at':
                $query->orderBy('created_at', $sortOrder);
                break;
            case 'distance':
                // Ya ordenado por distancia si se aplicó filtro de distancia
                if (empty($filters['lat']) || empty($filters['lng'])) {
                    $query->orderBy('average_rating', 'desc');
                }
                break;
            default:
                $query->orderBy('average_rating', $sortOrder);
                break;
        }

        // Paginación
        $perPage = $filters['per_page'] ?? 15;
        $destinos = $query->paginate($perPage);

        // Transformar resultados
        $destinosData = $destinos->getCollection()->map(function ($destino) use ($filters) {
            $data = [
                'id' => $destino->id,
                'name' => $destino->name,
                'slug' => $destino->slug,
                'description' => $destino->description,
                'price' => $destino->price,
                'rating' => $destino->average_rating ?? 0,
                'reviews_count' => $destino->reviews_count ?? 0,
                'main_image' => $destino->imagenes->first()?->url ?? null,
                'region' => $destino->region ? [
                    'id' => $destino->region->id,
                    'name' => $destino->region->name
                ] : null,
                'categorias' => $destino->categorias->map(function ($categoria) {
                    return [
                        'id' => $categoria->id,
                        'name' => $categoria->name
                    ];
                }),
                'caracteristicas' => $destino->caracteristicas->map(function ($caracteristica) {
                    return [
                        'id' => $caracteristica->id,
                        'name' => $caracteristica->name
                    ];
                }),
                'is_top' => $destino->is_top,
                'created_at' => $destino->created_at
            ];

            // Agregar distancia si se calculó
            if (isset($destino->distance)) {
                $data['distance'] = round($destino->distance, 2);
            }

            return $data;
        });

        // Estadísticas de búsqueda
        $searchTime = round((microtime(true) - $startTime) * 1000, 2);

        $filtersApplied = [
            'query' => $filters['query'] ?? null,
            'categorias_count' => !empty($filters['categorias']) ? count(explode(',', $filters['categorias'])) : 0,
            'caracteristicas_count' => !empty($filters['caracteristicas']) ? count(explode(',', $filters['caracteristicas'])) : 0,
            'price_range' => [
                'min' => $filters['precio_min'] ?? null,
                'max' => $filters['precio_max'] ?? null
            ],
            'rating_min' => $filters['rating_min'] ?? null,
            'distance_max' => $filters['distancia_max'] ?? null
        ];

        $searchStats = [
            'total_results' => $destinos->total(),
            'search_time_ms' => $searchTime,
            'cache_hit' => false
        ];

        $data = [
            'destinos' => $destinosData,
            'pagination' => [
                'current_page' => $destinos->currentPage(),
                'per_page' => $destinos->perPage(),
                'total' => $destinos->total(),
                'last_page' => $destinos->lastPage()
            ],
            'filters_applied' => $filtersApplied,
            'search_stats' => $searchStats
        ];

        return $this->sendResponse($data, 'Búsqueda avanzada realizada exitosamente.');
    }
} 