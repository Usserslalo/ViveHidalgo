<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\BaseController;
use App\Models\Categoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * @OA\Tag(
 *     name="Public Categories",
 *     description="Endpoints públicos para categorías"
 * )
 */
class CategoriaController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/v1/public/categorias",
     *     operationId="getPublicCategorias",
     *     summary="Lista de categorías",
     *     description="Devuelve todas las categorías disponibles con filtros, ordenamiento y estadísticas. Este endpoint utiliza cache para mejorar el rendimiento.",
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
     *         description="Filtrar solo categorías que tengan destinos publicados",
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
     *                 @OA\Property(property="categorias", type="object",
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="data", type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer"),
     *                             @OA\Property(property="name", type="string"),
     *                             @OA\Property(property="slug", type="string"),
     *                             @OA\Property(property="description", type="string", nullable=true),
     *                             @OA\Property(property="icon", type="string", nullable=true),
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
     *                     @OA\Property(property="total_categorias", type="integer"),
     *                     @OA\Property(property="categorias_con_destinos", type="integer"),
     *                     @OA\Property(property="total_destinos", type="integer")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Categorías recuperadas con éxito.")
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

            // Query base para categorías
            $query = Categoria::withCount([
                'destinos as total_destinos',
                'destinos as destinos_publicados' => function ($q) {
                    $q->where('status', 'published');
                }
            ]);

            // Filtro por categorías con destinos
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
            $cacheKey = 'public_categorias_' . md5(json_encode($request->all()));
            
            $categorias = Cache::remember($cacheKey, 300, function () use ($query, $perPage) {
                return $query->paginate($perPage);
            });

            // Transformar datos para respuesta optimizada
            $categorias->getCollection()->transform(function ($categoria) {
                return [
                    'id' => $categoria->id,
                    'name' => $categoria->name,
                    'slug' => $categoria->slug,
                    'description' => $categoria->description,
                    'icon' => $categoria->icon,
                    'total_destinos' => $categoria->total_destinos ?? 0,
                    'destinos_publicados' => $categoria->destinos_publicados ?? 0
                ];
            });

            // Calcular estadísticas generales
            $stats = Cache::remember('categorias_stats', 600, function () {
                return [
                    'total_categorias' => Categoria::count(),
                    'categorias_con_destinos' => Categoria::whereHas('destinos', function ($q) {
                        $q->where('status', 'published');
                    })->count(),
                    'total_destinos' => \App\Models\Destino::where('status', 'published')->count()
                ];
            });

            $responseData = [
                'categorias' => $categorias,
                'stats' => $stats
            ];

            return $this->successResponse($responseData, 'Categorías recuperadas con éxito.');

        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener categorías: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/public/categorias/{slug}",
     *     operationId="getPublicCategoriaBySlug",
     *     summary="Detalles de categoría por slug",
     *     description="Devuelve los detalles de una categoría específica usando su slug",
     *     tags={"Public Content"},
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Slug de la categoría",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="categoria", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="slug", type="string"),
     *                     @OA\Property(property="description", type="string", nullable=true),
     *                     @OA\Property(property="icon", type="string", nullable=true),
     *                     @OA\Property(property="total_destinos", type="integer"),
     *                     @OA\Property(property="destinos_publicados", type="integer")
     *                 ),
     *                 @OA\Property(property="destinos", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="slug", type="string"),
     *                         @OA\Property(property="short_description", type="string", nullable=true),
     *                         @OA\Property(property="imagen_principal", type="string", nullable=true),
     *                         @OA\Property(property="average_rating", type="number", format="float", nullable=true),
     *                         @OA\Property(property="reviews_count", type="integer", nullable=true)
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Detalles de la categoría recuperados con éxito.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Categoría no encontrada"
     *     )
     * )
     */
    public function show(string $slug): JsonResponse
    {
        try {
            $categoria = Categoria::where('slug', $slug)
                ->withCount([
                    'destinos as total_destinos',
                    'destinos as destinos_publicados' => function ($q) {
                        $q->where('status', 'published');
                    }
                ])
                ->with([
                    'destinos' => function ($q) {
                        $q->where('status', 'published')
                          ->with(['imagenes' => function ($img) {
                              $img->main();
                          }])
                          ->take(10);
                    }
                ])
                ->firstOrFail();

            // Transformar datos para respuesta optimizada
            $categoriaData = [
                'id' => $categoria->id,
                'name' => $categoria->name,
                'slug' => $categoria->slug,
                'description' => $categoria->description,
                'icon' => $categoria->icon,
                'total_destinos' => $categoria->total_destinos ?? 0,
                'destinos_publicados' => $categoria->destinos_publicados ?? 0
            ];

            $destinosData = $categoria->destinos->map(function ($destino) {
                return [
                    'id' => $destino->id,
                    'name' => $destino->name,
                    'slug' => $destino->slug,
                    'short_description' => $destino->short_description,
                    'imagen_principal' => $destino->imagenes->first() ? $destino->imagenes->first()->url : null,
                    'average_rating' => $destino->average_rating,
                    'reviews_count' => $destino->reviews_count
                ];
            });

            $responseData = [
                'categoria' => $categoriaData,
                'destinos' => $destinosData
            ];

            return $this->successResponse($responseData, 'Detalles de la categoría recuperados con éxito.');

        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la categoría: ' . $e->getMessage(), 500);
        }
    }
} 