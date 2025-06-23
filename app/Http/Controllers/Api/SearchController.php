<?php

namespace App\Http\Controllers\Api;

use App\Models\Destino;
use App\Models\Region;
use Illuminate\Http\Request;

class SearchController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/v1/search",
     *     operationId="search",
     *     tags={"Search"},
     *     summary="Realizar una búsqueda global",
     *     description="Busca destinos y regiones que coincidan con el término de búsqueda proporcionado.",
     *     @OA\Parameter(
     *         name="query",
     *         in="query",
     *         required=true,
     *         description="Término de búsqueda.",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Búsqueda exitosa",
     *         @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="destinos", type="array", @OA\Items(ref="#/components/schemas/Destino")),
     *                  @OA\Property(property="regiones", type="array", @OA\Items(ref="#/components/schemas/Region"))
     *              ),
     *              @OA\Property(property="message", type="string", example="Resultados de la búsqueda recuperados exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Solicitud incorrecta (ej. falta el query)",
     *     )
     * )
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        $query = $request->input('query');

        $destinos = Destino::search($query)
            ->where('status', 'published')
            ->get();

        $regiones = Region::search($query)->get();

        $results = [
            'destinos' => $destinos,
            'regiones' => $regiones,
        ];

        return $this->sendResponse($results, 'Resultados de la búsqueda recuperados exitosamente.');
    }
} 