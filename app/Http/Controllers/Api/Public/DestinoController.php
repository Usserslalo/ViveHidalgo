<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\BaseController;
use App\Models\Destino;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
} 