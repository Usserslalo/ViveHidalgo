<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\BaseController;
use App\Models\Region;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * @OA\Tag(
 *     name="Public Regions",
 *     description="Endpoints públicos para regiones"
 * )
 */
class RegionController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/v1/public/regiones",
     *     operationId="getPublicRegiones",
     *     summary="Lista de regiones",
     *     description="Devuelve todas las regiones disponibles con filtros, ordenamiento y estadísticas. Este endpoint utiliza cache para mejorar el rendimiento.",
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
     *         description="Filtrar solo regiones que tengan destinos publicados",
     *         required=false,
     *         @OA\Schema(type="boolean", default=false)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="regiones", type="object",
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="data", type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer"),
     *                             @OA\Property(property="name", type="string"),
     *                             @OA\Property(property="slug", type="string"),
     *                             @OA\Property(property="description", type="string", nullable=true),
     *                             @OA\Property(property="imagen_principal", type="string", nullable=true),
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
     *                     @OA\Property(property="total_regiones", type="integer"),
     *                     @OA\Property(property="regiones_con_destinos", type="integer"),
     *                     @OA\Property(property="total_destinos", type="integer")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Regiones recuperadas con éxito.")
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
            $hasDestinos = $request->boolean('has_destinos', false);

            // Query base para regiones
            $query = Region::withCount([
                'destinos as total_destinos',
                'destinos as destinos_publicados' => function ($q) {
                    $q->where('status', 'published');
                }
            ])
            ->with(['imagenes' => function ($q) { 
                $q->main(); 
            }]);

            // Filtro por regiones con destinos
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
            $cacheKey = 'public_regiones_' . md5(json_encode($request->all()));
            
            $regiones = Cache::remember($cacheKey, 300, function () use ($query, $perPage) {
                return $query->paginate($perPage);
            });

            // Transformar datos para respuesta optimizada
            $regiones->getCollection()->transform(function ($region) {
                return [
                    'id' => $region->id,
                    'name' => $region->name,
                    'slug' => $region->slug,
                    'description' => $region->description,
                    'imagen_principal' => $region->imagenes->first() ? $region->imagenes->first()->url : null,
                    'total_destinos' => $region->total_destinos ?? 0,
                    'destinos_publicados' => $region->destinos_publicados ?? 0
                ];
            });

            // Calcular estadísticas generales
            $stats = Cache::remember('regiones_stats', 600, function () {
                return [
                    'total_regiones' => Region::count(),
                    'regiones_con_destinos' => Region::whereHas('destinos', function ($q) {
                        $q->where('status', 'published');
                    })->count(),
                    'total_destinos' => \App\Models\Destino::where('status', 'published')->count()
                ];
            });

            $responseData = [
                'regiones' => $regiones,
                'stats' => $stats
            ];

            return $this->successResponse($responseData, 'Regiones recuperadas con éxito.');

        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener regiones: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/public/regiones/{slug}",
     *     summary="Detalles de región por slug",
     *     description="Devuelve los detalles de una región específica usando su slug",
     *     tags={"Public Regions"},
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Slug de la región",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Region"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Región no encontrada"
     *     )
     * )
     */
    public function show(string $slug): JsonResponse
    {
        $region = Region::where('slug', $slug)
            ->with([
                'imagenes' => function ($q) {
                    $q->ordered();
                },
                'destinos' => function ($q) {
                    $q->where('status', 'published')
                      ->with(['imagenes' => function ($img) {
                          $img->main();
                      }])
                      ->take(10);
                }
            ])
            ->firstOrFail();

        return $this->successResponse($region, 'Detalles de la región recuperados con éxito.');
    }
} 