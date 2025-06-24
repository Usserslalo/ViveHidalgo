<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\BaseController;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     *     summary="Lista de tags",
     *     description="Devuelve todos los tags activos",
     *     tags={"Public Tags"},
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Tag")),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $cacheKey = 'public_tags_' . md5(json_encode($request->all()));
        $tags = $this->paginateWithCache(
            Tag::active()->ordered()->withCount('destinos'),
            $perPage,
            $cacheKey,
            300 // 5 minutos
        );
        return $this->successResponse($tags, 'Tags recuperados con éxito.');
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