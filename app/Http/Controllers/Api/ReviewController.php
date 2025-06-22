<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Review;
use App\Models\Destino;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Tag(
 *     name="Reviews",
 *     description="API Endpoints para gestión de reseñas y calificaciones"
 * )
 */
class ReviewController extends BaseController
{
    /**
     * @OA\Post(
     *     path="/api/v1/user/reviews/{destino}",
     *     operationId="createReview",
     *     tags={"Reviews"},
     *     summary="Crear una nueva reseña para un destino",
     *     description="Crea una reseña para un destino específico. El usuario debe tener el destino en favoritos y no haberlo reseñado antes.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="destino",
     *         in="path",
     *         description="ID del destino",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ReviewCreateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reseña creada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Reseña creada exitosamente. Pendiente de aprobación."),
     *             @OA\Property(property="data", ref="#/components/schemas/Review")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado para crear reseña",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No puedes reseñar este destino. Asegúrate de tenerlo en favoritos y no haberlo reseñado antes.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos de validación incorrectos"
     *     )
     * )
     */
    public function store(Request $request, Destino $destino): JsonResponse
    {
        try {
            // Validar que el usuario puede crear la reseña
            if (!Auth::user()->can('create', [Review::class, $destino])) {
                return $this->sendError('No puedes reseñar este destino. Asegúrate de tenerlo en favoritos y no haberlo reseñado antes.', [], 403);
            }

            $validated = $request->validate([
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:1000',
            ]);

            $review = Review::create([
                'user_id' => Auth::id(),
                'destino_id' => $destino->id,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
                'is_approved' => false, // Por defecto requiere aprobación
            ]);

            return $this->sendResponse($review->load('user'), 'Reseña creada exitosamente. Pendiente de aprobación.');
        } catch (ValidationException $e) {
            return $this->sendError('Datos de validación incorrectos.', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError('Error al crear la reseña.', [], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/v1/user/reviews/{review}",
     *     operationId="updateReview",
     *     tags={"Reviews"},
     *     summary="Actualizar una reseña existente",
     *     description="Actualiza una reseña. Solo el autor de la reseña puede editarla.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="review",
     *         in="path",
     *         description="ID de la reseña",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/ReviewUpdateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reseña actualizada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Reseña actualizada exitosamente."),
     *             @OA\Property(property="data", ref="#/components/schemas/Review")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado para editar la reseña"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Datos de validación incorrectos"
     *     )
     * )
     */
    public function update(Request $request, Review $review): JsonResponse
    {
        try {
            // Validar que el usuario puede actualizar la reseña
            if (!Auth::user()->can('update', $review)) {
                return $this->sendError('No puedes editar esta reseña.', [], 403);
            }

            $validated = $request->validate([
                'rating' => 'sometimes|integer|min:1|max:5',
                'comment' => 'sometimes|nullable|string|max:1000',
            ]);

            $review->update($validated);

            return $this->sendResponse($review->load('user'), 'Reseña actualizada exitosamente.');
        } catch (ValidationException $e) {
            return $this->sendError('Datos de validación incorrectos.', $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->sendError('Error al actualizar la reseña.', [], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/user/reviews/{review}",
     *     operationId="deleteReview",
     *     tags={"Reviews"},
     *     summary="Eliminar una reseña",
     *     description="Elimina una reseña. Solo el autor de la reseña puede eliminarla.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="review",
     *         in="path",
     *         description="ID de la reseña",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reseña eliminada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Reseña eliminada exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado para eliminar la reseña"
     *     )
     * )
     */
    public function destroy(Review $review): JsonResponse
    {
        try {
            // Validar que el usuario puede eliminar la reseña
            if (!Auth::user()->can('delete', $review)) {
                return $this->sendError('No puedes eliminar esta reseña.', [], 403);
            }

            $review->delete();

            return $this->sendResponse([], 'Reseña eliminada exitosamente.');
        } catch (\Exception $e) {
            return $this->sendError('Error al eliminar la reseña.', [], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/public/destinos/{destino}/reviews",
     *     operationId="getDestinoReviews",
     *     tags={"Reviews"},
     *     summary="Obtener reseñas de un destino",
     *     description="Obtiene las reseñas aprobadas de un destino específico. No requiere autenticación.",
     *     @OA\Parameter(
     *         name="destino",
     *         in="path",
     *         description="ID del destino",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reseñas obtenidas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Reseñas obtenidas exitosamente."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Review")),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="total", type="integer", example=25)
     *             )
     *         )
     *     )
     * )
     */
    public function getDestinoReviews(Destino $destino): JsonResponse
    {
        try {
            $reviews = $destino->reviews()
                ->where('is_approved', true)
                ->with('user:id,name')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return $this->sendResponse($reviews, 'Reseñas obtenidas exitosamente.');
        } catch (\Exception $e) {
            return $this->sendError('Error al obtener las reseñas.', [], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/user/reviews",
     *     operationId="getUserReviews",
     *     tags={"Reviews"},
     *     summary="Obtener reseñas del usuario autenticado",
     *     description="Obtiene todas las reseñas del usuario autenticado, tanto aprobadas como pendientes.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Reseñas obtenidas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tus reseñas obtenidas exitosamente."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Review")),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=10),
     *                 @OA\Property(property="total", type="integer", example=5)
     *             )
     *         )
     *     )
     * )
     */
    public function getUserReviews(): JsonResponse
    {
        try {
            $reviews = Auth::user()->reviews()
                ->with('destino:id,name')
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            return $this->sendResponse($reviews, 'Tus reseñas obtenidas exitosamente.');
        } catch (\Exception $e) {
            return $this->sendError('Error al obtener tus reseñas.', [], 500);
        }
    }
} 