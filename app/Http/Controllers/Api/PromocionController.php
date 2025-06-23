<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Destino;
use App\Models\Promocion;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class PromocionController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/v1/public/promociones",
     *     operationId="getPublicPromociones",
     *     tags={"Promociones"},
     *     summary="Obtener todas las promociones activas",
     *     description="Devuelve una lista paginada de todas las promociones que están actualmente activas.",
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa",
     *         @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Promocion")),
     *              @OA\Property(property="message", type="string", example="Promociones activas recuperadas exitosamente.")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $now = Carbon::now();
        $promociones = Promocion::with('destino:id,name,slug')
            ->where('is_active', true)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->latest()
            ->paginate(15);

        return $this->sendResponse($promociones, 'Promociones activas recuperadas exitosamente.');
    }

    /**
     * @OA\Get(
     *     path="/api/v1/public/destinos/{destino}/promociones",
     *     operationId="getPromocionesForDestino",
     *     tags={"Promociones"},
     *     summary="Obtener promociones activas para un destino específico",
     *     description="Devuelve las promociones activas para un destino dado por su ID.",
     *     @OA\Parameter(
     *         name="destino",
     *         in="path",
     *         required=true,
     *         description="ID del Destino",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa"
     *     )
     * )
     */
    public function forDestino(Destino $destino): JsonResponse
    {
        $now = Carbon::now();
        $promociones = $destino->promociones()
            ->where('is_active', true)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->latest()
            ->get();

        return $this->sendResponse($promociones, 'Promociones para el destino recuperadas exitosamente.');
    }
} 