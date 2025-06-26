<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\BaseController;
use App\Models\PromocionDestacada;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PromocionDestacadaController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/v1/public/promociones-destacadas",
     *     operationId="getPromocionesDestacadas",
     *     tags={"Public Promociones"},
     *     summary="Obtener promociones destacadas vigentes",
     *     description="Retorna las promociones destacadas que están actualmente vigentes.",
     *     @OA\Response(
     *         response=200,
     *         description="Promociones recuperadas exitosamente",
     *         @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="array", @OA\Items(
     *                  @OA\Property(property="id", type="integer"),
     *                  @OA\Property(property="titulo", type="string"),
     *                  @OA\Property(property="descripcion", type="string"),
     *                  @OA\Property(property="imagen", type="string"),
     *                  @OA\Property(property="fecha_inicio", type="string", format="date-time"),
     *                  @OA\Property(property="fecha_fin", type="string", format="date-time"),
     *                  @OA\Property(property="destinos", type="array", @OA\Items(ref="#/components/schemas/Destino"))
     *              )),
     *              @OA\Property(property="message", type="string", example="Promociones destacadas recuperadas exitosamente.")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        return $this->getCachedData('public_promociones_destacadas', function () {
            return $this->performPromocionesSearch();
        }, 1800); // Cache por 30 minutos
    }

    /**
     * Obtener las promociones destacadas vigentes
     */
    private function performPromocionesSearch(): JsonResponse
    {
        $promociones = PromocionDestacada::vigentes()
            ->with(['destinos' => function ($query) {
                $query->published()
                    ->with(['region:id,name', 'imagenes' => function ($q) { $q->main(); }]);
            }])
            ->orderBy('fecha_inicio', 'asc')
            ->get()
            ->map(function ($promocion) {
                return [
                    'id' => $promocion->id,
                    'titulo' => $promocion->titulo,
                    'descripcion' => $promocion->descripcion,
                    'imagen' => $promocion->imagen,
                    'fecha_inicio' => $promocion->fecha_inicio->toISOString(),
                    'fecha_fin' => $promocion->fecha_fin->toISOString(),
                    'dias_restantes' => now()->diffInDays($promocion->fecha_fin, false),
                    'destinos' => $promocion->destinos->map(function ($destino) {
                        return [
                            'id' => $destino->id,
                            'name' => $destino->name,
                            'slug' => $destino->slug,
                            'imagen_principal' => $destino->imagenes->first() ? $destino->imagenes->first()->url : null,
                            'rating' => $destino->average_rating,
                            'region' => $destino->region ? $destino->region->name : null,
                        ];
                    }),
                ];
            });

        return $this->successResponse($promociones, 'Promociones destacadas recuperadas exitosamente.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/public/promociones-destacadas/{id}",
     *     operationId="getPromocionDestacada",
     *     tags={"Public Promociones"},
     *     summary="Obtener una promoción destacada específica",
     *     description="Retorna los detalles de una promoción destacada específica.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID de la promoción",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Promoción recuperada exitosamente",
     *         @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="id", type="integer"),
     *                  @OA\Property(property="titulo", type="string"),
     *                  @OA\Property(property="descripcion", type="string"),
     *                  @OA\Property(property="imagen", type="string"),
     *                  @OA\Property(property="fecha_inicio", type="string", format="date-time"),
     *                  @OA\Property(property="fecha_fin", type="string", format="date-time"),
     *                  @OA\Property(property="destinos", type="array", @OA\Items(ref="#/components/schemas/Destino"))
     *              ),
     *              @OA\Property(property="message", type="string", example="Promoción destacada recuperada exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Promoción no encontrada"
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        $promocion = PromocionDestacada::with(['destinos' => function ($query) {
            $query->published()
                ->with(['region:id,name', 'imagenes' => function ($q) { $q->main(); }]);
        }])->find($id);

        if (!$promocion) {
            return $this->errorResponse('Promoción no encontrada.', 404);
        }

        $data = [
            'id' => $promocion->id,
            'titulo' => $promocion->titulo,
            'descripcion' => $promocion->descripcion,
            'imagen' => $promocion->imagen,
            'fecha_inicio' => $promocion->fecha_inicio->toISOString(),
            'fecha_fin' => $promocion->fecha_fin->toISOString(),
            'dias_restantes' => now()->diffInDays($promocion->fecha_fin, false),
            'esta_vigente' => $promocion->esta_vigente,
            'destinos' => $promocion->destinos->map(function ($destino) {
                return [
                    'id' => $destino->id,
                    'name' => $destino->name,
                    'slug' => $destino->slug,
                    'imagen_principal' => $destino->imagenes->first() ? $destino->imagenes->first()->url : null,
                    'rating' => $destino->average_rating,
                    'region' => $destino->region ? $destino->region->name : null,
                ];
            }),
        ];

        return $this->successResponse($data, 'Promoción destacada recuperada exitosamente.');
    }
} 