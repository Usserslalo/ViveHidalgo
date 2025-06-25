<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\BaseController;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * @OA\Tag(
 *     name="Public Tags",
 *     description="Endpoints públicos para tags"
 * )
 */
class TagController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/v1/public/tags",
     *     operationId="getPublicTags",
     *     summary="Lista de tags",
     *     description="Devuelve todos los tags activos con filtros, ordenamiento y estadísticas. Este endpoint utiliza cache para mejorar el rendimiento.",
     *     tags={"Public Content"},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número de página para paginación",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Elementos por página (máximo 50)",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, maximum=50)
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Ordenar por: 'name' (alfabético), 'destinos' (más destinos), 'recent' (más recientes)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"name", "destinos", "recent"}, default="name")
     *     ),
     *     @OA\Parameter(
     *         name="has_destinos",
     *         in="query",
     *         description="Filtrar solo tags que tengan destinos publicados",
     *         required=false,
     *         @OA\Schema(type="string", enum={"true", "false", "1", "0"}, default="false")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="tags", type="object",
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="data", type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer"),
     *                             @OA\Property(property="name", type="string"),
     *                             @OA\Property(property="slug", type="string"),
     *                             @OA\Property(property="description", type="string", nullable=true),
     *                             @OA\Property(property="color", type="string", nullable=true),
     *                             @OA\Property(property="total_destinos", type="integer"),
     *                             @OA\Property(property="destinos_publicados", type="integer")
     *                         )
     *                     ),
     *                     @OA\Property(property="first_page_url", type="string"),
     *                     @OA\Property(property="from", type="integer", nullable=true),
     *                     @OA\Property(property="last_page", type="integer"),
     *                     @OA\Property(property="last_page_url", type="string"),
     *                     @OA\Property(property="path", type="string"),
     *                     @OA\Property(property="per_page", type="integer"),
     *                     @OA\Property(property="to", type="integer", nullable=true),
     *                     @OA\Property(property="total", type="integer")
     *                 ),
     *                 @OA\Property(property="stats", type="object",
     *                     @OA\Property(property="total_tags", type="integer"),
     *                     @OA\Property(property="tags_con_destinos", type="integer"),
     *                     @OA\Property(property="total_destinos", type="integer")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Tags recuperados con éxito.")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Validar parámetros
            $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50',
                'sort' => 'nullable|in:name,destinos,recent',
                'has_destinos' => 'nullable|in:true,false,1,0'
            ]);

            $perPage = min($request->input('per_page', 15), 50);
            $sort = $request->input('sort', 'name');
            $hasDestinos = in_array($request->input('has_destinos'), ['true', '1']);

            // Query base para tags activos
            $query = Tag::active()
                ->withCount([
                    'destinos as total_destinos',
                    'destinos as destinos_publicados' => function ($q) {
                        $q->where('status', 'published');
                    }
                ]);

            // Filtro por tags con destinos
            if ($hasDestinos) {
                $query->whereHas('destinos', function ($q) {
                    $q->where('status', 'published');
                });
            }

            // Ordenamiento
            switch ($sort) {
                case 'destinos':
                    $query->orderBy('destinos_publicados', 'desc');
                    break;
                case 'recent':
                    $query->orderBy('created_at', 'desc');
                    break;
                default: // name
                    $query->orderBy('name', 'asc');
                    break;
            }

            // Cache key único para esta consulta
            $cacheKey = 'public_tags_' . md5(json_encode($request->all()));
            
            $tags = Cache::remember($cacheKey, 300, function () use ($query, $perPage) {
                return $query->paginate($perPage);
            });

            // Transformar datos para respuesta optimizada
            $tags->getCollection()->transform(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                    'description' => $tag->description,
                    'color' => $tag->color,
                    'total_destinos' => $tag->total_destinos ?? 0,
                    'destinos_publicados' => $tag->destinos_publicados ?? 0
                ];
            });

            // Calcular estadísticas generales
            $stats = Cache::remember('tags_stats', 600, function () {
                return [
                    'total_tags' => Tag::active()->count(),
                    'tags_con_destinos' => Tag::active()->whereHas('destinos', function ($q) {
                        $q->where('status', 'published');
                    })->count(),
                    'total_destinos' => \App\Models\Destino::where('status', 'published')->count()
                ];
            });

            $responseData = [
                'tags' => $tags,
                'stats' => $stats
            ];

            return $this->successResponse($responseData, 'Tags recuperados con éxito.');

        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener tags: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/public/tags/{slug}",
     *     summary="Detalles de tag por slug",
     *     description="Devuelve los detalles de un tag específico usando su slug",
     *     tags={"Public Tags"},
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Slug del tag",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Tag"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Tag no encontrado"
     *     )
     * )
     */
    public function show(string $slug): JsonResponse
    {
        $tag = Tag::where('slug', $slug)
            ->active()
            ->with([
                'destinos' => function ($q) {
                    $q->where('status', 'published')
                      ->with(['region', 'imagenes' => function ($img) {
                          $img->main();
                      }])
                      ->orderByDesc('average_rating')
                      ->take(20);
                }
            ])
            ->firstOrFail();

        return $this->successResponse($tag, 'Detalles del tag recuperados con éxito.');
    }
} 