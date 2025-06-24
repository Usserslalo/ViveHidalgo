<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\BaseController;
use App\Models\Region;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     *     summary="Lista de regiones",
     *     description="Devuelve todas las regiones disponibles. Este endpoint utiliza cache para mejorar el rendimiento. Los datos pueden actualizarse cada 5 minutos.",
     *     tags={"Public Regions"},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Elementos por página (paginación, default: 15)",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Region")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $cacheKey = 'public_regiones_' . md5(json_encode($request->all()));
        $regiones = $this->paginateWithCache(
            Region::with(['imagenes' => function ($q) { $q->main(); }]),
            $perPage,
            $cacheKey,
            300 // 5 minutos
        );
        return $this->successResponse($regiones, 'Regiones recuperadas con éxito.');
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