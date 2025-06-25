<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Destino;
use App\Models\Promocion;
use App\Models\Region;
use App\Models\Categoria;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PromocionController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/v1/public/promociones",
     *     operationId="getPublicPromociones",
     *     tags={"Public Content"},
     *     summary="Obtener promociones activas",
     *     description="Devuelve una lista paginada de promociones activas con filtros y ordenamiento.",
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
     *         description="Número de promociones por página (máximo 50)",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, maximum=50)
     *     ),
     *     @OA\Parameter(
     *         name="region_id",
     *         in="query",
     *         description="Filtrar por región específica",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="category_id",
     *         in="query",
     *         description="Filtrar por categoría específica",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="min_discount",
     *         in="query",
     *         description="Descuento mínimo (porcentaje)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=0, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Ordenar por: 'recent' (más recientes), 'ending_soon' (terminan pronto), 'discount' (mayor descuento)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"recent", "ending_soon", "discount"}, default="recent")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="promociones", type="object",
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="data", type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer"),
     *                             @OA\Property(property="titulo", type="string"),
     *                             @OA\Property(property="descripcion", type="string"),
     *                             @OA\Property(property="descuento_porcentaje", type="integer"),
     *                             @OA\Property(property="codigo", type="string", nullable=true),
     *                             @OA\Property(property="fecha_inicio", type="string", format="date"),
     *                             @OA\Property(property="fecha_fin", type="string", format="date"),
     *                             @OA\Property(property="dias_restantes", type="integer"),
     *                             @OA\Property(property="destino", type="object",
     *                                 @OA\Property(property="id", type="integer"),
     *                                 @OA\Property(property="name", type="string"),
     *                                 @OA\Property(property="slug", type="string"),
     *                                 @OA\Property(property="region", type="string", nullable=true)
     *                             )
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
     *                 @OA\Property(property="filtros_disponibles", type="object",
     *                     @OA\Property(property="regiones", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="categorias", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="rangos_descuento", type="array", @OA\Items(type="object"))
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Promociones activas recuperadas exitosamente.")
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
                'region_id' => 'nullable|integer|exists:regions,id',
                'category_id' => 'nullable|integer|exists:categorias,id',
                'min_discount' => 'nullable|integer|min:0|max:100',
                'sort' => 'nullable|in:recent,ending_soon,discount'
            ]);

            $perPage = min($request->input('per_page', 15), 50);
            $sort = $request->input('sort', 'recent');
            $now = Carbon::now();

            // Query base para promociones activas
            $query = Promocion::with([
                'destino:id,name,slug,region_id',
                'destino.region:id,name'
            ])
            ->where('is_active', true)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now);

            // Filtro por región
            if ($request->has('region_id')) {
                $query->whereHas('destino', function ($q) use ($request) {
                    $q->where('region_id', $request->input('region_id'));
                });
            }

            // Filtro por categoría
            if ($request->has('category_id')) {
                $query->whereHas('destino.categorias', function ($q) use ($request) {
                    $q->where('categorias.id', $request->input('category_id'));
                });
            }

            // Filtro por descuento mínimo
            if ($request->has('min_discount')) {
                $query->where('discount_percentage', '>=', $request->input('min_discount'));
            }

            // Ordenamiento
            switch ($sort) {
                case 'ending_soon':
                    $query->orderBy('end_date', 'asc');
                    break;
                case 'discount':
                    $query->orderBy('discount_percentage', 'desc');
                    break;
                default: // recent
                    $query->orderBy('created_at', 'desc');
                    break;
            }

            // Cache key único para esta consulta
            $cacheKey = 'public_promociones_' . md5(json_encode($request->all()));
            
            $promociones = Cache::remember($cacheKey, 300, function () use ($query, $perPage) {
                return $query->paginate($perPage);
            });

            // Transformar datos para respuesta optimizada
            $promociones->getCollection()->transform(function ($promocion) use ($now) {
                $endDate = Carbon::parse($promocion->end_date);
                $diasRestantes = $now->diffInDays($endDate, false);
                
                return [
                    'id' => $promocion->id,
                    'titulo' => $promocion->title,
                    'descripcion' => $promocion->description,
                    'descuento_porcentaje' => $promocion->discount_percentage,
                    'codigo' => $promocion->code,
                    'fecha_inicio' => $promocion->start_date->format('Y-m-d'),
                    'fecha_fin' => $promocion->end_date->format('Y-m-d'),
                    'dias_restantes' => max(0, $diasRestantes),
                    'destino' => [
                        'id' => $promocion->destino->id,
                        'name' => $promocion->destino->name,
                        'slug' => $promocion->destino->slug,
                        'region' => $promocion->destino->region ? $promocion->destino->region->name : null
                    ]
                ];
            });

            // Obtener filtros disponibles para el frontend
            $filtrosDisponibles = Cache::remember('promociones_filtros', 600, function () {
                return [
                    'regiones' => Region::select('id', 'name')->get(),
                    'categorias' => Categoria::select('id', 'name')->get(),
                    'rangos_descuento' => [
                        ['min' => 0, 'max' => 25, 'label' => '0-25%'],
                        ['min' => 25, 'max' => 50, 'label' => '25-50%'],
                        ['min' => 50, 'max' => 75, 'label' => '50-75%'],
                        ['min' => 75, 'max' => 100, 'label' => '75%+']
                    ]
                ];
            });

            $responseData = [
                'promociones' => $promociones,
                'filtros_disponibles' => $filtrosDisponibles
            ];

            return $this->successResponse($responseData, 'Promociones activas recuperadas exitosamente.');

        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener promociones: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/public/destinos/{destino}/promociones",
     *     operationId="getPromocionesForDestino",
     *     tags={"Public Content"},
     *     summary="Obtener promociones activas para un destino específico",
     *     description="Devuelve las promociones activas para un destino específico con paginación y ordenamiento.",
     *     @OA\Parameter(
     *         name="destino",
     *         in="path",
     *         required=true,
     *         description="ID del Destino",
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
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
     *         description="Número de promociones por página (máximo 50)",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, maximum=50)
     *     ),
     *     @OA\Parameter(
     *         name="min_discount",
     *         in="query",
     *         description="Descuento mínimo (porcentaje)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=0, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Ordenar por: 'recent' (más recientes), 'ending_soon' (terminan pronto), 'discount' (mayor descuento)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"recent", "ending_soon", "discount"}, default="recent")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="destino", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="slug", type="string"),
     *                     @OA\Property(property="region", type="string", nullable=true)
     *                 ),
     *                 @OA\Property(property="promociones", type="object",
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="data", type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer"),
     *                             @OA\Property(property="titulo", type="string"),
     *                             @OA\Property(property="descripcion", type="string"),
     *                             @OA\Property(property="descuento_porcentaje", type="integer"),
     *                             @OA\Property(property="codigo", type="string", nullable=true),
     *                             @OA\Property(property="fecha_inicio", type="string", format="date"),
     *                             @OA\Property(property="fecha_fin", type="string", format="date"),
     *                             @OA\Property(property="dias_restantes", type="integer")
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
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Promociones para el destino recuperadas exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Destino no encontrado"
     *     )
     * )
     */
    public function forDestino(Request $request, Destino $destino): JsonResponse
    {
        try {
            // Validar parámetros
            $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50',
                'min_discount' => 'nullable|integer|min:0|max:100',
                'sort' => 'nullable|in:recent,ending_soon,discount'
            ]);

            $perPage = min($request->input('per_page', 10), 50);
            $sort = $request->input('sort', 'recent');
            $now = Carbon::now();

            // Query base para promociones activas del destino
            $query = $destino->promociones()
                ->where('is_active', true)
                ->where('start_date', '<=', $now)
                ->where('end_date', '>=', $now);

            // Filtro por descuento mínimo
            if ($request->has('min_discount')) {
                $query->where('discount_percentage', '>=', $request->input('min_discount'));
            }

            // Ordenamiento
            switch ($sort) {
                case 'ending_soon':
                    $query->orderBy('end_date', 'asc');
                    break;
                case 'discount':
                    $query->orderBy('discount_percentage', 'desc');
                    break;
                default: // recent
                    $query->orderBy('created_at', 'desc');
                    break;
            }

            // Cache key único para esta consulta
            $cacheKey = "destino_{$destino->id}_promociones_" . md5(json_encode($request->all()));
            
            $promociones = Cache::remember($cacheKey, 300, function () use ($query, $perPage) {
                return $query->paginate($perPage);
            });

            // Transformar datos para respuesta optimizada
            $promociones->getCollection()->transform(function ($promocion) use ($now) {
                $endDate = Carbon::parse($promocion->end_date);
                $diasRestantes = $now->diffInDays($endDate, false);
                
                return [
                    'id' => $promocion->id,
                    'titulo' => $promocion->title,
                    'descripcion' => $promocion->description,
                    'descuento_porcentaje' => $promocion->discount_percentage,
                    'codigo' => $promocion->code,
                    'fecha_inicio' => $promocion->start_date->format('Y-m-d'),
                    'fecha_fin' => $promocion->end_date->format('Y-m-d'),
                    'dias_restantes' => max(0, $diasRestantes)
                ];
            });

            // Preparar respuesta
            $responseData = [
                'destino' => [
                    'id' => $destino->id,
                    'name' => $destino->name,
                    'slug' => $destino->slug,
                    'region' => $destino->region ? $destino->region->name : null
                ],
                'promociones' => $promociones
            ];

            return $this->successResponse($responseData, 'Promociones para el destino recuperadas exitosamente.');

        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener promociones del destino: ' . $e->getMessage(), 500);
        }
    }
} 