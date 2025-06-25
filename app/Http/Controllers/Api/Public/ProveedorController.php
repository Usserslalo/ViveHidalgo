<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\BaseController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * @OA\Tag(
 *     name="Public Providers",
 *     description="Endpoints públicos para proveedores"
 * )
 */
class ProveedorController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/v1/public/proveedores",
     *     operationId="getPublicProveedores",
     *     summary="Lista de proveedores verificados",
     *     description="Devuelve todos los proveedores verificados y activos con filtros, ordenamiento y estadísticas. Este endpoint utiliza cache para mejorar el rendimiento.",
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
     *         name="business_type",
     *         in="query",
     *         description="Filtrar por tipo de negocio",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="city",
     *         in="query",
     *         description="Filtrar por ciudad",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Ordenar por: 'name' (alfabético), 'recent' (más recientes), 'popular' (más destinos), 'rating' (mejor calificación)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"name", "recent", "popular", "rating"}, default="name")
     *     ),
     *     @OA\Parameter(
     *         name="has_destinos",
     *         in="query",
     *         description="Filtrar solo proveedores que tengan destinos publicados",
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
     *                 @OA\Property(property="proveedores", type="object",
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="data", type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer"),
     *                             @OA\Property(property="name", type="string"),
     *                             @OA\Property(property="company_name", type="string", nullable=true),
     *                             @OA\Property(property="slug", type="string"),
     *                             @OA\Property(property="email", type="string"),
     *                             @OA\Property(property="phone", type="string", nullable=true),
     *                             @OA\Property(property="profile_photo", type="string", nullable=true),
     *                             @OA\Property(property="company_description", type="string", nullable=true),
     *                             @OA\Property(property="business_type", type="string", nullable=true),
     *                             @OA\Property(property="city", type="string", nullable=true),
     *                             @OA\Property(property="website", type="string", nullable=true),
     *                             @OA\Property(property="logo_url", type="string", nullable=true),
     *                             @OA\Property(property="verified_at", type="string", format="date-time", nullable=true),
     *                             @OA\Property(property="member_since", type="string", format="date"),
     *                             @OA\Property(property="stats", type="object",
     *                                 @OA\Property(property="destinos_count", type="integer"),
     *                                 @OA\Property(property="promociones_count", type="integer"),
     *                                 @OA\Property(property="total_reviews", type="integer"),
     *                                 @OA\Property(property="average_rating", type="number", format="float")
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
     *                 @OA\Property(property="stats", type="object",
     *                     @OA\Property(property="total_proveedores", type="integer"),
     *                     @OA\Property(property="proveedores_con_destinos", type="integer"),
     *                     @OA\Property(property="total_destinos", type="integer"),
     *                     @OA\Property(property="por_tipo", type="object"),
     *                     @OA\Property(property="por_ciudad", type="object")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Proveedores recuperados con éxito.")
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
                'business_type' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
                'sort' => 'nullable|in:name,recent,popular,rating',
                'has_destinos' => 'nullable|in:true,false,1,0'
            ]);

            $perPage = min($request->input('per_page', 15), 50);
            $sort = $request->input('sort', 'name');
            $hasDestinos = in_array($request->input('has_destinos'), ['true', '1']);

            // Query base para proveedores verificados y activos
            $query = User::where('is_verified_provider', true)
                ->where('is_active', true)
                ->whereHas('roles', function ($q) {
                    $q->where('name', 'provider');
                })
                ->withCount([
                    'destinos as destinos_count' => function ($q) {
                        $q->where('status', 'published');
                    },
                    'promociones as promociones_count' => function ($q) {
                        $q->where('status', 'active');
                    }
                ])
                ->withAvg('destinos as average_rating', 'average_rating')
                ->withSum('destinos as total_reviews', 'reviews_count');

            // Filtro por tipo de negocio
            if ($request->has('business_type')) {
                $query->where('business_type', 'like', '%' . $request->input('business_type') . '%');
            }

            // Filtro por ciudad
            if ($request->has('city')) {
                $query->where('city', 'like', '%' . $request->input('city') . '%');
            }

            // Filtro por proveedores con destinos
            if ($hasDestinos) {
                $query->whereHas('destinos', function ($q) {
                    $q->where('status', 'published');
                });
            }

            // Ordenamiento
            switch ($sort) {
                case 'recent':
                    $query->orderBy('verified_at', 'desc');
                    break;
                case 'popular':
                    $query->orderBy('destinos_count', 'desc');
                    break;
                case 'rating':
                    $query->orderBy('average_rating', 'desc');
                    break;
                default: // name
                    $query->orderBy('company_name', 'asc')
                          ->orderBy('name', 'asc');
                    break;
            }

            // Cache key único para esta consulta
            $cacheKey = 'public_proveedores_' . md5(json_encode($request->all()));
            
            $proveedores = Cache::remember($cacheKey, 300, function () use ($query, $perPage) {
                return $query->paginate($perPage);
            });

            // Transformar datos para respuesta optimizada
            $proveedores->getCollection()->transform(function ($proveedor) {
                return [
                    'id' => $proveedor->id,
                    'name' => $proveedor->name,
                    'company_name' => $proveedor->company_name,
                    'slug' => $proveedor->slug ?? strtolower(str_replace(' ', '-', $proveedor->company_name ?? $proveedor->name)),
                    'email' => $proveedor->email,
                    'phone' => $proveedor->phone,
                    'profile_photo' => $proveedor->profile_photo,
                    'company_description' => $proveedor->company_description,
                    'business_type' => $proveedor->business_type,
                    'city' => $proveedor->city,
                    'website' => $proveedor->website,
                    'logo_url' => $proveedor->logo_url,
                    'verified_at' => $proveedor->verified_at,
                    'member_since' => $proveedor->created_at->format('Y-m-d'),
                    'stats' => [
                        'destinos_count' => $proveedor->destinos_count ?? 0,
                        'promociones_count' => $proveedor->promociones_count ?? 0,
                        'total_reviews' => $proveedor->total_reviews ?? 0,
                        'average_rating' => round($proveedor->average_rating ?? 0, 1)
                    ]
                ];
            });

            // Calcular estadísticas generales
            $stats = Cache::remember('proveedores_stats', 600, function () {
                $porTipo = User::where('is_verified_provider', true)
                    ->where('is_active', true)
                    ->whereHas('roles', function ($q) {
                        $q->where('name', 'provider');
                    })
                    ->whereNotNull('business_type')
                    ->selectRaw('business_type, COUNT(*) as count')
                    ->groupBy('business_type')
                    ->pluck('count', 'business_type')
                    ->toArray();

                $porCiudad = User::where('is_verified_provider', true)
                    ->where('is_active', true)
                    ->whereHas('roles', function ($q) {
                        $q->where('name', 'provider');
                    })
                    ->whereNotNull('city')
                    ->selectRaw('city, COUNT(*) as count')
                    ->groupBy('city')
                    ->pluck('count', 'city')
                    ->toArray();

                return [
                    'total_proveedores' => User::where('is_verified_provider', true)
                        ->where('is_active', true)
                        ->whereHas('roles', function ($q) {
                            $q->where('name', 'provider');
                        })->count(),
                    'proveedores_con_destinos' => User::where('is_verified_provider', true)
                        ->where('is_active', true)
                        ->whereHas('roles', function ($q) {
                            $q->where('name', 'provider');
                        })
                        ->whereHas('destinos', function ($q) {
                            $q->where('status', 'published');
                        })->count(),
                    'total_destinos' => \App\Models\Destino::where('status', 'published')->count(),
                    'por_tipo' => $porTipo,
                    'por_ciudad' => $porCiudad
                ];
            });

            $responseData = [
                'proveedores' => $proveedores,
                'stats' => $stats
            ];

            return $this->successResponse($responseData, 'Proveedores recuperados con éxito.');

        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener proveedores: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/public/proveedores/{slug}",
     *     operationId="getPublicProveedorBySlug",
     *     summary="Detalles de proveedor por slug",
     *     description="Devuelve los detalles de un proveedor específico usando su slug",
     *     tags={"Public Content"},
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Slug del proveedor",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="proveedor", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="company_name", type="string", nullable=true),
     *                     @OA\Property(property="slug", type="string"),
     *                     @OA\Property(property="email", type="string"),
     *                     @OA\Property(property="phone", type="string", nullable=true),
     *                     @OA\Property(property="profile_photo", type="string", nullable=true),
     *                     @OA\Property(property="company_description", type="string", nullable=true),
     *                     @OA\Property(property="business_type", type="string", nullable=true),
     *                     @OA\Property(property="city", type="string", nullable=true),
     *                     @OA\Property(property="website", type="string", nullable=true),
     *                     @OA\Property(property="logo_url", type="string", nullable=true),
     *                     @OA\Property(property="verified_at", type="string", format="date-time", nullable=true),
     *                     @OA\Property(property="member_since", type="string", format="date"),
     *                     @OA\Property(property="business_hours", type="object", nullable=true),
     *                     @OA\Property(property="stats", type="object")
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
     *                 ),
     *                 @OA\Property(property="promociones", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="titulo", type="string"),
     *                         @OA\Property(property="descripcion", type="string"),
     *                         @OA\Property(property="descuento", type="integer"),
     *                         @OA\Property(property="fecha_inicio", type="string", format="date"),
     *                         @OA\Property(property="fecha_fin", type="string", format="date")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Detalles del proveedor recuperados con éxito.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Proveedor no encontrado"
     *     )
     * )
     */
    public function show(string $slug): JsonResponse
    {
        try {
            $proveedor = User::where('is_verified_provider', true)
                ->where('is_active', true)
                ->whereHas('roles', function ($q) {
                    $q->where('name', 'provider');
                })
                ->get()
                ->first(function ($user) use ($slug) {
                    $companySlug = $user->company_name ? strtolower(str_replace(' ', '-', $user->company_name)) : null;
                    $nameSlug = strtolower(str_replace(' ', '-', $user->name));
                    return $companySlug === $slug || $nameSlug === $slug;
                });

            if (!$proveedor) {
                return $this->notFoundResponse('Proveedor no encontrado');
            }

            // Cargar relaciones y estadísticas
            $proveedor->loadCount([
                'destinos as destinos_count' => function ($q) {
                    $q->where('status', 'published');
                },
                'promociones as promociones_count' => function ($q) {
                    $q->where('status', 'active');
                }
            ]);
            $proveedor->load([
                'destinos' => function ($q) {
                    $q->where('status', 'published')
                      ->with(['imagenes' => function ($img) {
                          $img->main();
                      }])
                      ->take(10);
                },
                'promociones' => function ($q) {
                    $q->where('status', 'active')
                      ->where('fecha_fin', '>=', now())
                      ->take(5);
                }
            ]);

            // Transformar datos para respuesta optimizada
            $proveedorData = [
                'id' => $proveedor->id,
                'name' => $proveedor->name,
                'company_name' => $proveedor->company_name,
                'slug' => $proveedor->company_name ? strtolower(str_replace(' ', '-', $proveedor->company_name)) : strtolower(str_replace(' ', '-', $proveedor->name)),
                'email' => $proveedor->email,
                'phone' => $proveedor->phone,
                'profile_photo' => $proveedor->profile_photo,
                'company_description' => $proveedor->company_description,
                'business_type' => $proveedor->business_type,
                'city' => $proveedor->city,
                'website' => $proveedor->website,
                'logo_url' => $proveedor->logo_url,
                'verified_at' => $proveedor->verified_at,
                'member_since' => $proveedor->created_at->format('Y-m-d'),
                'business_hours' => $proveedor->business_hours,
                'stats' => [
                    'destinos_count' => $proveedor->destinos_count ?? 0,
                    'promociones_count' => $proveedor->promociones_count ?? 0,
                    'total_reviews' => $proveedor->destinos->sum('reviews_count'),
                    'average_rating' => round($proveedor->destinos->avg('average_rating') ?? 0, 1)
                ]
            ];

            $destinosData = $proveedor->destinos->map(function ($destino) {
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

            $promocionesData = $proveedor->promociones->map(function ($promocion) {
                return [
                    'id' => $promocion->id,
                    'titulo' => $promocion->titulo,
                    'descripcion' => $promocion->descripcion,
                    'descuento' => $promocion->descuento,
                    'fecha_inicio' => $promocion->fecha_inicio->format('Y-m-d'),
                    'fecha_fin' => $promocion->fecha_fin->format('Y-m-d')
                ];
            });

            $responseData = [
                'proveedor' => $proveedorData,
                'destinos' => $destinosData,
                'promociones' => $promocionesData
            ];

            return $this->successResponse($responseData, 'Detalles del proveedor recuperados con éxito.');

        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener el proveedor: ' . $e->getMessage(), 500);
        }
    }
} 