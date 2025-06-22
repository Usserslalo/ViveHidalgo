<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Destino;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class FavoritoController extends BaseController
{
    /**
     * @OA\Post(
     *      path="/api/v1/favoritos/{destino_id}",
     *      operationId="addToFavorites",
     *      tags={"Favorites"},
     *      summary="Add a destination to user favorites",
     *      description="Adds a destination to the authenticated user's favorites list.",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="destino_id",
     *          in="path",
     *          description="ID of the destination to add to favorites",
     *          required=true,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Destination added to favorites successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Destino añadido a favoritos"),
     *              @OA\Property(property="data", type="object")
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Destination not found"
     *      ),
     *      @OA\Response(
     *          response=409,
     *          description="Destination already in favorites"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated"
     *      )
     * )
     */
    public function addToFavorites(int $destinoId): JsonResponse
    {
        $user = Auth::user();
        
        // Verificar que el destino existe y está publicado
        $destino = Destino::where('id', $destinoId)
                         ->where('status', 'published')
                         ->first();
        
        if (!$destino) {
            return $this->errorResponse('Destino no encontrado o no disponible', 404);
        }
        
        // Verificar si ya está en favoritos
        if ($user->favoritos()->where('destino_id', $destinoId)->exists()) {
            return $this->errorResponse('El destino ya está en tus favoritos', 409);
        }
        
        // Añadir a favoritos
        $user->favoritos()->attach($destinoId);
        
        return $this->successResponse(null, 'Destino añadido a favoritos correctamente');
    }

    /**
     * @OA\Delete(
     *      path="/api/v1/favoritos/{destino_id}",
     *      operationId="removeFromFavorites",
     *      tags={"Favorites"},
     *      summary="Remove a destination from user favorites",
     *      description="Removes a destination from the authenticated user's favorites list.",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="destino_id",
     *          in="path",
     *          description="ID of the destination to remove from favorites",
     *          required=true,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Destination removed from favorites successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="message", type="string", example="Destino removido de favoritos"),
     *              @OA\Property(property="data", type="object")
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Destination not found in favorites"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated"
     *      )
     * )
     */
    public function removeFromFavorites(int $destinoId): JsonResponse
    {
        $user = Auth::user();
        
        // Verificar si está en favoritos
        if (!$user->favoritos()->where('destino_id', $destinoId)->exists()) {
            return $this->errorResponse('El destino no está en tus favoritos', 404);
        }
        
        // Remover de favoritos
        $user->favoritos()->detach($destinoId);
        
        return $this->successResponse(null, 'Destino removido de favoritos correctamente');
    }

    /**
     * @OA\Get(
     *      path="/api/v1/favoritos",
     *      operationId="getUserFavorites",
     *      tags={"Favorites"},
     *      summary="Get user's favorite destinations",
     *      description="Returns a paginated list of the authenticated user's favorite destinations.",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="page",
     *          in="query",
     *          description="Page number for pagination",
     *          required=false,
     *          @OA\Schema(type="integer", default=1)
     *      ),
     *      @OA\Parameter(
     *          name="per_page",
     *          in="query",
     *          description="Number of items per page",
     *          required=false,
     *          @OA\Schema(type="integer", default=15)
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
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated"
     *      )
     * )
     */
    public function getUserFavorites(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        $perPage = $request->input('per_page', 15);
        
        $favoritos = $user->favoritos()
                         ->with(['region', 'categorias', 'caracteristicas' => function ($query) {
                             $query->activas();
                         }])
                         ->where('status', 'published')
                         ->paginate($perPage);
        
        return $this->successResponse($favoritos, 'Lista de favoritos recuperada correctamente');
    }

    /**
     * @OA\Get(
     *      path="/api/v1/favoritos/check/{destino_id}",
     *      operationId="checkIfFavorite",
     *      tags={"Favorites"},
     *      summary="Check if a destination is in user favorites",
     *      description="Returns whether a specific destination is in the authenticated user's favorites.",
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="destino_id",
     *          in="path",
     *          description="ID of the destination to check",
     *          required=true,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              @OA\Property(property="success", type="boolean", example=true),
     *              @OA\Property(property="data", type="object",
     *                  @OA\Property(property="is_favorite", type="boolean", example=true)
     *              ),
     *              @OA\Property(property="message", type="string")
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated"
     *      )
     * )
     */
    public function checkIfFavorite(int $destinoId): JsonResponse
    {
        $user = Auth::user();
        
        $isFavorite = $user->favoritos()->where('destino_id', $destinoId)->exists();
        
        return $this->successResponse(['is_favorite' => $isFavorite], 'Estado de favorito verificado');
    }
} 