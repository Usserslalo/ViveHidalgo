<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\BaseController;
use App\Models\Destino;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DestinoController extends BaseController
{
    /**
     * @OA\Get(
     *      path="/api/v1/public/destinos",
     *      operationId="getPublicDestinosList",
     *      tags={"Public Content"},
     *      summary="Get list of published destinations",
     *      description="Returns a paginated list of published destinations with optional geolocation filtering.",
     *      @OA\Parameter(
     *          name="region_id",
     *          in="query",
     *          description="Filter by region ID",
     *          required=false,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Parameter(
     *          name="category_id",
     *          in="query",
     *          description="Filter by category ID",
     *          required=false,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Parameter(
     *          name="caracteristicas",
     *          in="query",
     *          description="Filter by characteristics (comma-separated IDs)",
     *          required=false,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Parameter(
     *          name="latitude",
     *          in="query",
     *          description="Latitude for distance calculation",
     *          required=false,
     *          @OA\Schema(type="number", format="float")
     *      ),
     *      @OA\Parameter(
     *          name="longitude",
     *          in="query",
     *          description="Longitude for distance calculation",
     *          required=false,
     *          @OA\Schema(type="number", format="float")
     *      ),
     *      @OA\Parameter(
     *          name="radius",
     *          in="query",
     *          description="Radius in kilometers for filtering destinations",
     *          required=false,
     *          @OA\Schema(type="number", format="float")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="current_page", type="integer"),
     *                  @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Destino")),
     *                  @OA\Property(property="first_page_url", type="string"),
     *                  @OA\Property(property="from", type="integer"),
     *                  @OA\Property(property="last_page", type="integer"),
     *                  @OA\Property(property="last_page_url", type="string"),
     *                  @OA\Property(property="path", type="string"),
     *                  @OA\Property(property="per_page", type="integer"),
     *                  @OA\Property(property="to", type="integer"),
     *                  @OA\Property(property="total", type="integer"),
     *              ),
     *              @OA\Property(property="message", type="string")
     *          )
     *      ),
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Destino::query()
            ->with(['region', 'categorias', 'caracteristicas' => function ($query) {
                $query->activas();
            }])
            ->where('status', 'published');

        // Filter by Region
        if ($request->has('region_id')) {
            $query->where('region_id', $request->input('region_id'));
        }

        // Filter by Category
        if ($request->has('category_id')) {
            $query->whereHas('categorias', function ($q) use ($request) {
                $q->where('categorias.id', $request->input('category_id'));
            });
        }

        // Filter by Characteristics
        if ($request->has('caracteristicas')) {
            $caracteristicaIds = explode(',', $request->input('caracteristicas'));
            $query->whereHas('caracteristicas', function ($q) use ($caracteristicaIds) {
                $q->whereIn('caracteristicas.id', $caracteristicaIds);
            });
        }

        // Geolocation filtering
        if ($request->has('latitude') && $request->has('longitude')) {
            $latitude = $request->input('latitude');
            $longitude = $request->input('longitude');
            $radius = $request->input('radius', 50); // Default 50km radius

            $query->withDistance($latitude, $longitude)
                  ->withinRadius($latitude, $longitude, $radius);
        }
        
        $destinos = $query->paginate(15);

        return $this->successResponse($destinos, 'Destinos publicados recuperados con éxito.');
    }

    /**
     * @OA\Get(
     *      path="/api/v1/public/destinos/{slug}",
     *      operationId="getPublicDestinoBySlug",
     *      tags={"Public Content"},
     *      summary="Get a single destination's details",
     *      description="Returns details for a single published destination.",
     *      @OA\Parameter(
     *          name="slug",
     *          in="path",
     *          description="Slug of the destination",
     *          required=true,
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", ref="#/components/schemas/Destino"),
     *              @OA\Property(property="message", type="string")
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Destination not found"
     *      )
     * )
     */
    public function show(string $slug): JsonResponse
    {
        $destino = Destino::where('slug', $slug)
            ->where('status', 'published')
            ->with(['region', 'categorias', 'caracteristicas' => function ($query) {
                $query->activas();
            }, 'user' => function ($query) {
                // Solo seleccionamos la información pública del proveedor
                $query->select('id', 'name'); 
            }])
            ->firstOrFail();

        return $this->successResponse($destino, 'Detalles del destino recuperados con éxito.');
    }
} 