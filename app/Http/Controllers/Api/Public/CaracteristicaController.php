<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\BaseController;
use App\Models\Caracteristica;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * @OA\Tag(
 *     name="Public Characteristics",
 *     description="Endpoints públicos para características"
 * )
 */
class CaracteristicaController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/v1/public/caracteristicas",
     *     operationId="getPublicCaracteristicas",
     *     summary="Lista de características",
     *     description="Devuelve todas las características activas con filtros, ordenamiento y estadísticas. Este endpoint utiliza cache para mejorar el rendimiento.",
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
     *         name="tipo",
     *         in="query",
     *         description="Filtrar por tipo: amenidad, actividad, cultural, natural, especial, alojamiento, general",
     *         required=false,
     *         @OA\Schema(type="string", enum={"amenidad", "actividad", "cultural", "natural", "especial", "alojamiento", "general"})
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
     *         description="Filtrar solo características que tengan destinos publicados",
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
     *                 @OA\Property(property="caracteristicas", type="object",
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="data", type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer"),
     *                             @OA\Property(property="nombre", type="string"),
     *                             @OA\Property(property="slug", type="string"),
     *                             @OA\Property(property="tipo", type="string"),
     *                             @OA\Property(property="descripcion", type="string", nullable=true),
     *                             @OA\Property(property="icono", type="string", nullable=true),
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
     *                     @OA\Property(property="total_caracteristicas", type="integer"),
     *                     @OA\Property(property="caracteristicas_con_destinos", type="integer"),
     *                     @OA\Property(property="total_destinos", type="integer"),
     *                     @OA\Property(property="por_tipo", type="object",
     *                         @OA\Property(property="amenidad", type="integer"),
     *                         @OA\Property(property="actividad", type="integer"),
     *                         @OA\Property(property="cultural", type="integer"),
     *                         @OA\Property(property="natural", type="integer"),
     *                         @OA\Property(property="especial", type="integer"),
     *                         @OA\Property(property="alojamiento", type="integer"),
     *                         @OA\Property(property="general", type="integer")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Características recuperadas con éxito.")
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
                'tipo' => 'nullable|in:amenidad,actividad,cultural,natural,especial,alojamiento,general',
                'sort' => 'nullable|in:name,destinos,recent',
                'has_destinos' => 'nullable|in:true,false,1,0'
            ]);

            $perPage = min($request->input('per_page', 15), 50);
            $sort = $request->input('sort', 'name');
            $hasDestinos = in_array($request->input('has_destinos'), ['true', '1']);

            // Query base para características activas
            $query = Caracteristica::activas();

            // Filtro por tipo
            if ($request->has('tipo')) {
                $query->where('tipo', $request->input('tipo'));
            }

            // Ordenamiento
            switch ($sort) {
                case 'recent':
                    $query->orderBy('created_at', 'desc');
                    break;
                default: // name
                    $query->orderBy('nombre', 'asc');
                    break;
            }

            $caracteristicas = $query->paginate($perPage);

            // Transformar datos para respuesta optimizada
            $caracteristicas->getCollection()->transform(function ($caracteristica) {
                return [
                    'id' => $caracteristica->id,
                    'nombre' => $caracteristica->nombre,
                    'slug' => $caracteristica->slug,
                    'tipo' => $caracteristica->tipo,
                    'descripcion' => $caracteristica->descripcion,
                    'icono' => $caracteristica->icono,
                    'total_destinos' => 0,
                    'destinos_publicados' => 0
                ];
            });

            // Estadísticas básicas
            $stats = [
                'total_caracteristicas' => Caracteristica::activas()->count(),
                'caracteristicas_con_destinos' => 0,
                'total_destinos' => 0,
                'por_tipo' => []
            ];

            $responseData = [
                'caracteristicas' => $caracteristicas,
                'stats' => $stats
            ];

            return $this->successResponse($responseData, 'Características recuperadas con éxito.');

        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener características: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/public/caracteristicas/{slug}",
     *     operationId="getPublicCaracteristicaBySlug",
     *     summary="Detalles de característica por slug",
     *     description="Devuelve los detalles de una característica específica usando su slug",
     *     tags={"Public Content"},
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Slug de la característica",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="caracteristica", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="nombre", type="string"),
     *                     @OA\Property(property="slug", type="string"),
     *                     @OA\Property(property="tipo", type="string"),
     *                     @OA\Property(property="descripcion", type="string", nullable=true),
     *                     @OA\Property(property="icono", type="string", nullable=true),
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
     *             @OA\Property(property="message", type="string", example="Detalles de la característica recuperados con éxito.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Característica no encontrada"
     *     )
     * )
     */
    public function show(string $slug): JsonResponse
    {
        try {
            $caracteristica = Caracteristica::where('slug', $slug)
                ->activas()
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
            $caracteristicaData = [
                'id' => $caracteristica->id,
                'nombre' => $caracteristica->nombre,
                'slug' => $caracteristica->slug,
                'tipo' => $caracteristica->tipo,
                'descripcion' => $caracteristica->descripcion,
                'icono' => $caracteristica->icono,
                'total_destinos' => $caracteristica->total_destinos ?? 0,
                'destinos_publicados' => $caracteristica->destinos_publicados ?? 0
            ];

            $destinosData = $caracteristica->destinos->map(function ($destino) {
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
                'caracteristica' => $caracteristicaData,
                'destinos' => $destinosData
            ];

            return $this->successResponse($responseData, 'Detalles de la característica recuperados con éxito.');

        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la característica: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Método de prueba simple
     */
    public function test(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Controlador de características funcionando',
            'data' => [
                'timestamp' => now(),
                'controller' => 'CaracteristicaController'
            ]
        ]);
    }
} 