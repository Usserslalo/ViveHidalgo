<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\BaseController;
use App\Models\Evento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EventoController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/v1/public/eventos",
     *     operationId="getEventos",
     *     tags={"Public Content"},
     *     summary="Listar eventos turísticos",
     *     description="Obtiene una lista paginada de eventos turísticos con filtros y ordenamiento",
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtrar por estado",
     *         required=false,
     *         @OA\Schema(type="string", enum={"upcoming", "ongoing", "past"}, example="upcoming")
     *     ),
     *     @OA\Parameter(
     *         name="is_featured",
     *         in="query",
     *         description="Solo eventos destacados",
     *         required=false,
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Parameter(
     *         name="destino_id",
     *         in="query",
     *         description="Filtrar por destino específico",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="categoria_id",
     *         in="query",
     *         description="Filtrar por categoría",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
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
     *         name="date_from",
     *         in="query",
     *         description="Fecha desde (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-02-01")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="Fecha hasta (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-12-31")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Campo de ordenamiento",
     *         required=false,
     *         @OA\Schema(type="string", enum={"start_date", "name", "price", "capacity"}, example="start_date")
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
     *         description="Lista de eventos obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="eventos", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Festival de la Barbacoa"),
     *                     @OA\Property(property="slug", type="string", example="festival-barbacoa-2025"),
     *                     @OA\Property(property="short_description", type="string", example="El mejor festival de barbacoa del estado"),
     *                     @OA\Property(property="start_date", type="string", format="date-time"),
     *                     @OA\Property(property="end_date", type="string", format="date-time"),
     *                     @OA\Property(property="location", type="string", example="Plaza Principal de Pachuca"),
     *                     @OA\Property(property="price", type="number", format="float", example=150.00),
     *                     @OA\Property(property="capacity", type="integer", example=500),
     *                     @OA\Property(property="current_attendees", type="integer", example=250),
     *                     @OA\Property(property="available_capacity", type="integer", example=250),
     *                     @OA\Property(property="capacity_percentage", type="number", format="float", example=50.0),
     *                     @OA\Property(property="duration_days", type="integer", example=3),
     *                     @OA\Property(property="days_until_start", type="integer", example=15),
     *                     @OA\Property(property="is_featured", type="boolean", example=true),
     *                     @OA\Property(property="main_image", type="string", example="https://example.com/image.jpg"),
     *                     @OA\Property(property="destino", type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Pachuca"),
     *                         @OA\Property(property="slug", type="string", example="pachuca")
     *                     ),
     *                     @OA\Property(property="organizer_name", type="string", example="Asociación Gastronómica"),
     *                     @OA\Property(property="status", type="string", example="published")
     *                 )),
     *                 @OA\Property(property="pagination", type="object",
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="per_page", type="integer", example=15),
     *                     @OA\Property(property="total", type="integer", example=25),
     *                     @OA\Property(property="last_page", type="integer", example=2)
     *                 ),
     *                 @OA\Property(property="stats", type="object",
     *                     @OA\Property(property="total_eventos", type="integer", example=25),
     *                     @OA\Property(property="upcoming_eventos", type="integer", example=15),
     *                     @OA\Property(property="ongoing_eventos", type="integer", example=3),
     *                     @OA\Property(property="featured_eventos", type="integer", example=5)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Lista de eventos obtenida exitosamente.")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Validar parámetros
            $validated = $request->validate([
                'status' => 'nullable|string|in:upcoming,ongoing,past',
                'is_featured' => 'nullable|boolean',
                'destino_id' => 'nullable|integer|exists:destinos,id',
                'categoria_id' => 'nullable|integer|exists:categorias,id',
                'price_min' => 'nullable|numeric|min:0',
                'price_max' => 'nullable|numeric|min:0|gte:price_min',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'sort_by' => 'nullable|string|in:start_date,name,price,capacity',
                'sort_order' => 'nullable|string|in:asc,desc',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            // Crear clave de cache
            $cacheKey = 'public_eventos_' . md5(serialize($validated));

            return $this->getCachedData($cacheKey, function () use ($validated) {
                return $this->getEventosList($validated);
            }, 300); // Cache por 5 minutos

        } catch (\Exception $e) {
            Log::error('Error getting eventos: ' . $e->getMessage());
            return $this->sendError('Error al obtener eventos: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/public/eventos/{slug}",
     *     operationId="getEvento",
     *     tags={"Public Content"},
     *     summary="Obtener detalles de un evento",
     *     description="Retorna información detallada de un evento específico",
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         description="Slug del evento",
     *         required=true,
     *         @OA\Schema(type="string", example="festival-barbacoa-2025")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Evento obtenido exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Festival de la Barbacoa"),
     *                 @OA\Property(property="slug", type="string", example="festival-barbacoa-2025"),
     *                 @OA\Property(property="description", type="string", example="Descripción completa del evento..."),
     *                 @OA\Property(property="short_description", type="string", example="El mejor festival de barbacoa"),
     *                 @OA\Property(property="start_date", type="string", format="date-time"),
     *                 @OA\Property(property="end_date", type="string", format="date-time"),
     *                 @OA\Property(property="location", type="string", example="Plaza Principal de Pachuca"),
     *                 @OA\Property(property="latitude", type="number", format="float", example=20.1234),
     *                 @OA\Property(property="longitude", type="number", format="float", example=-98.5678),
     *                 @OA\Property(property="price", type="number", format="float", example=150.00),
     *                 @OA\Property(property="capacity", type="integer", example=500),
     *                 @OA\Property(property="current_attendees", type="integer", example=250),
     *                 @OA\Property(property="available_capacity", type="integer", example=250),
     *                 @OA\Property(property="capacity_percentage", type="number", format="float", example=50.0),
     *                 @OA\Property(property="duration_days", type="integer", example=3),
     *                 @OA\Property(property="days_until_start", type="integer", example=15),
     *                 @OA\Property(property="is_featured", type="boolean", example=true),
     *                 @OA\Property(property="main_image", type="string", example="https://example.com/image.jpg"),
     *                 @OA\Property(property="gallery", type="array", @OA\Items(type="string")),
     *                 @OA\Property(property="contact_info", type="object"),
     *                 @OA\Property(property="organizer_name", type="string", example="Asociación Gastronómica"),
     *                 @OA\Property(property="organizer_email", type="string", example="info@festival.com"),
     *                 @OA\Property(property="organizer_phone", type="string", example="+52 771 123 4567"),
     *                 @OA\Property(property="website_url", type="string", example="https://festival.com"),
     *                 @OA\Property(property="social_media", type="object"),
     *                 @OA\Property(property="destino", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Pachuca"),
     *                     @OA\Property(property="slug", type="string", example="pachuca")
     *                 ),
     *                 @OA\Property(property="categorias", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Gastronomía")
     *                 )),
     *                 @OA\Property(property="caracteristicas", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Música en vivo")
     *                 )),
     *                 @OA\Property(property="tags", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Festival")
     *                 )),
     *                 @OA\Property(property="status", type="string", example="published"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             ),
     *             @OA\Property(property="message", type="string", example="Evento obtenido exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Evento no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Evento no encontrado.")
     *         )
     *     )
     * )
     */
    public function show(string $slug): JsonResponse
    {
        try {
            $evento = Evento::with([
                'destino:id,name,slug',
                'categorias:id,name',
                'caracteristicas:id,name',
                'tags:id,name'
            ])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->first();

            if (!$evento) {
                return $this->sendError('Evento no encontrado.', [], 404);
            }

            // Cache individual del evento
            $cacheKey = "evento_detail_{$slug}";
            return $this->getCachedData($cacheKey, function () use ($evento) {
                return $this->sendResponse($this->transformEvento($evento), 'Evento obtenido exitosamente.');
            }, 600); // Cache por 10 minutos

        } catch (\Exception $e) {
            Log::error('Error getting evento: ' . $e->getMessage());
            return $this->sendError('Error al obtener evento: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Obtener lista de eventos con filtros
     */
    private function getEventosList(array $filters): JsonResponse
    {
        $query = Evento::with([
            'destino:id,name,slug',
            'categorias:id,name',
            'caracteristicas:id,name',
            'tags:id,name'
        ])
        ->where('status', 'published');

        // Filtro por estado
        if (!empty($filters['status'])) {
            switch ($filters['status']) {
                case 'upcoming':
                    $query->upcoming();
                    break;
                case 'ongoing':
                    $query->ongoing();
                    break;
                case 'past':
                    $query->where('end_date', '<', now());
                    break;
            }
        }

        // Filtro por destacados
        if (isset($filters['is_featured'])) {
            $query->where('is_featured', $filters['is_featured']);
        }

        // Filtro por destino
        if (!empty($filters['destino_id'])) {
            $query->where('destino_id', $filters['destino_id']);
        }

        // Filtro por categoría
        if (!empty($filters['categoria_id'])) {
            $query->whereHas('categorias', function ($q) use ($filters) {
                $q->where('categorias.id', $filters['categoria_id']);
            });
        }

        // Filtro por precio
        if (!empty($filters['price_min'])) {
            $query->where('price', '>=', $filters['price_min']);
        }
        if (!empty($filters['price_max'])) {
            $query->where('price', '<=', $filters['price_max']);
        }

        // Filtro por fechas
        if (!empty($filters['date_from'])) {
            $query->where('start_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('end_date', '<=', $filters['date_to']);
        }

        // Ordenamiento
        $sortBy = $filters['sort_by'] ?? 'start_date';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $filters['per_page'] ?? 15;
        $eventos = $query->paginate($perPage);

        // Transformar resultados
        $eventosData = $eventos->getCollection()->map(function ($evento) {
            return $this->transformEvento($evento);
        });

        // Estadísticas
        $stats = [
            'total_eventos' => Evento::where('status', 'published')->count(),
            'upcoming_eventos' => Evento::where('status', 'published')->upcoming()->count(),
            'ongoing_eventos' => Evento::where('status', 'published')->ongoing()->count(),
            'featured_eventos' => Evento::where('status', 'published')->where('is_featured', true)->count()
        ];

        $data = [
            'eventos' => $eventosData,
            'pagination' => [
                'current_page' => $eventos->currentPage(),
                'per_page' => $eventos->perPage(),
                'total' => $eventos->total(),
                'last_page' => $eventos->lastPage()
            ],
            'stats' => $stats
        ];

        return $this->sendResponse($data, 'Lista de eventos obtenida exitosamente.');
    }

    /**
     * Transformar evento para respuesta
     */
    private function transformEvento(Evento $evento): array
    {
        return [
            'id' => $evento->id,
            'name' => $evento->name,
            'slug' => $evento->slug,
            'description' => $evento->description,
            'short_description' => $evento->short_description,
            'start_date' => $evento->start_date,
            'end_date' => $evento->end_date,
            'location' => $evento->location,
            'latitude' => $evento->latitude,
            'longitude' => $evento->longitude,
            'price' => $evento->price,
            'capacity' => $evento->capacity,
            'current_attendees' => $evento->current_attendees,
            'available_capacity' => $evento->available_capacity,
            'capacity_percentage' => $evento->capacity_percentage,
            'duration_days' => $evento->duration_days,
            'days_until_start' => $evento->days_until_start,
            'is_featured' => $evento->is_featured,
            'main_image' => $evento->main_image,
            'gallery' => $evento->gallery ?? [],
            'contact_info' => $evento->contact_info ?? [],
            'organizer_name' => $evento->organizer_name,
            'organizer_email' => $evento->organizer_email,
            'organizer_phone' => $evento->organizer_phone,
            'website_url' => $evento->website_url,
            'social_media' => $evento->social_media ?? [],
            'destino' => $evento->destino ? [
                'id' => $evento->destino->id,
                'name' => $evento->destino->name,
                'slug' => $evento->destino->slug
            ] : null,
            'categorias' => $evento->categorias->map(function ($categoria) {
                return [
                    'id' => $categoria->id,
                    'name' => $categoria->name
                ];
            }),
            'caracteristicas' => $evento->caracteristicas->map(function ($caracteristica) {
                return [
                    'id' => $caracteristica->id,
                    'name' => $caracteristica->name
                ];
            }),
            'tags' => $evento->tags->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name
                ];
            }),
            'status' => $evento->status,
            'created_at' => $evento->created_at
        ];
    }
} 