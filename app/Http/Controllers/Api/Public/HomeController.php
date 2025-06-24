<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\BaseController;
use App\Models\Destino;
use App\Models\Promocion;
use App\Models\Region;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * @OA\Tag(
 *     name="Home",
 *     description="Endpoints para la página de inicio pública"
 * )
 */
class HomeController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/v1/public/home",
     *     summary="Datos para la página de inicio",
     *     description="Devuelve destinos top, promociones activas, regiones destacadas, tags populares y recomendaciones.",
     *     tags={"Home"},
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="top_destinos", type="array", @OA\Items(ref="#/components/schemas/Destino")),
     *                 @OA\Property(property="promociones", type="array", @OA\Items(ref="#/components/schemas/Promocion")),
     *                 @OA\Property(property="regiones_destacadas", type="array", @OA\Items(ref="#/components/schemas/Region")),
     *                 @OA\Property(property="tags_populares", type="array", @OA\Items(ref="#/components/schemas/Tag")),
     *                 @OA\Property(property="recomendaciones", type="array", @OA\Items(ref="#/components/schemas/Destino"))
     *             ),
     *             @OA\Property(property="message", type="string", example="Datos de home recuperados con éxito.")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $data = Cache::remember('public_home', 60, function () {
            return [
                'top_destinos' => Destino::with(['region', 'imagenes' => function ($q) { $q->main(); }])
                    ->where('status', 'published')
                    ->where('is_top', true)
                    ->orderByDesc('average_rating')
                    ->take(8)
                    ->get(),
                'promociones' => Promocion::with(['destino', 'imagenes' => function ($q) { $q->main(); }])
                    ->where('is_active', true)
                    ->orderByDesc('start_date')
                    ->take(6)
                    ->get(),
                'regiones_destacadas' => Region::with(['imagenes' => function ($q) { $q->main(); }])
                    ->orderByDesc('id')
                    ->take(6)
                    ->get(),
                'tags_populares' => Tag::withCount('destinos')
                    ->orderByDesc('destinos_count')
                    ->active()
                    ->take(8)
                    ->get(),
                'recomendaciones' => Destino::with(['region', 'imagenes' => function ($q) { $q->main(); }])
                    ->where('status', 'published')
                    ->orderByDesc('average_rating')
                    ->take(8)
                    ->get(),
            ];
        });

        return $this->successResponse($data, 'Datos de home recuperados con éxito.');
    }
} 