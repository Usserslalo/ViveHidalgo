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
     *     tags={"Public Content"},
     *     summary="Obtener reseñas de un destino",
     *     description="Obtiene las reseñas aprobadas de un destino específico. No requiere autenticación.",
     *     @OA\Parameter(
     *         name="destino",
     *         in="path",
     *         description="ID del destino",
     *         required=true,
     *         @OA\Schema(type="integer", minimum=1)
     *     ),
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
     *         description="Número de reseñas por página (máximo 50)",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, maximum=50)
     *     ),
     *     @OA\Parameter(
     *         name="rating",
     *         in="query",
     *         description="Filtrar por calificación específica (1-5)",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=5)
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Ordenar por: 'recent' (más recientes), 'oldest' (más antiguas), 'rating' (mejor calificadas)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"recent", "oldest", "rating"}, default="recent")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reseñas obtenidas exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="destino", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="slug", type="string")
     *                 ),
     *                 @OA\Property(property="reviews", type="object",
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="data", type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="id", type="integer"),
     *                             @OA\Property(property="rating", type="integer"),
     *                             @OA\Property(property="comment", type="string", nullable=true),
     *                             @OA\Property(property="created_at", type="string", format="date-time"),
     *                             @OA\Property(property="user", type="object",
     *                                 @OA\Property(property="id", type="integer"),
     *                                 @OA\Property(property="name", type="string")
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
     *                     @OA\Property(property="average_rating", type="number", format="float"),
     *                     @OA\Property(property="total_reviews", type="integer"),
     *                     @OA\Property(property="rating_distribution", type="object",
     *                         @OA\Property(property="5", type="integer"),
     *                         @OA\Property(property="4", type="integer"),
     *                         @OA\Property(property="3", type="integer"),
     *                         @OA\Property(property="2", type="integer"),
     *                         @OA\Property(property="1", type="integer")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Reseñas obtenidas exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Destino no encontrado"
     *     )
     * )
     */
    public function getDestinoReviews(Request $request, Destino $destino): JsonResponse
    {
        try {
            // Validar parámetros
            $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50',
                'rating' => 'nullable|integer|min:1|max:5',
                'sort' => 'nullable|in:recent,oldest,rating'
            ]);

            $perPage = min($request->input('per_page', 10), 50);
            $sort = $request->input('sort', 'recent');

            // Query base para reseñas aprobadas
            $query = $destino->reviews()
                ->where('is_approved', true)
                ->with('user:id,name');

            // Filtro por calificación
            if ($request->has('rating')) {
                $query->where('rating', $request->input('rating'));
            }

            // Ordenamiento
            switch ($sort) {
                case 'oldest':
                    $query->orderBy('created_at', 'asc');
                    break;
                case 'rating':
                    $query->orderBy('rating', 'desc')->orderBy('created_at', 'desc');
                    break;
                default: // recent
                    $query->orderBy('created_at', 'desc');
                    break;
            }

            $reviews = $query->paginate($perPage);

            // Calcular estadísticas
            $stats = [
                'average_rating' => $destino->average_rating ?? 0,
                'total_reviews' => $destino->reviews()->where('is_approved', true)->count(),
                'rating_distribution' => $destino->reviews()
                    ->where('is_approved', true)
                    ->selectRaw('rating, COUNT(*) as count')
                    ->groupBy('rating')
                    ->pluck('count', 'rating')
                    ->toArray()
            ];

            // Preparar respuesta
            $responseData = [
                'destino' => [
                    'id' => $destino->id,
                    'name' => $destino->name,
                    'slug' => $destino->slug
                ],
                'reviews' => $reviews,
                'stats' => $stats
            ];

            return $this->successResponse($responseData, 'Reseñas obtenidas exitosamente.');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener las reseñas: ' . $e->getMessage(), 500);
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

    /**
     * @OA\Get(
     *     path="/api/v1/public/destinos/{slug}/reviews/summary",
     *     operationId="getReviewsSummary",
     *     tags={"Public Reviews"},
     *     summary="Obtener resumen de reseñas de un destino",
     *     description="Retorna un resumen estadístico de las reseñas de un destino",
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         required=true,
     *         description="Slug del destino",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Resumen obtenido exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="destino", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="slug", type="string")
     *                 ),
     *                 @OA\Property(property="summary", type="object",
     *                     @OA\Property(property="average_rating", type="number", format="float"),
     *                     @OA\Property(property="total_reviews", type="integer"),
     *                     @OA\Property(property="rating_distribution", type="object"),
     *                     @OA\Property(property="recent_reviews", type="array", @OA\Items(ref="#/components/schemas/Review"))
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Resumen obtenido exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Destino no encontrado"
     *     )
     * )
     */
    public function summary(string $slug): JsonResponse
    {
        try {
            $destino = Destino::where('slug', $slug)
                ->where('status', 'published')
                ->first();

            if (!$destino) {
                return $this->errorResponse('Destino no encontrado.', 404);
            }

            $reviews = $destino->reviews()->where('is_approved', true)->get();

            $summary = [
                'average_rating' => $destino->average_rating ?? 0,
                'total_reviews' => $reviews->count(),
                'rating_distribution' => $reviews->groupBy('rating')
                    ->map->count()
                    ->toArray(),
                'recent_reviews' => $reviews->take(3)
                    ->map(function ($review) {
                        return [
                            'id' => $review->id,
                            'rating' => $review->rating,
                            'comment' => $review->comment,
                            'created_at' => $review->created_at,
                            'user' => [
                                'id' => $review->user->id,
                                'name' => $review->user->name
                            ]
                        ];
                    })
            ];

            $data = [
                'destino' => [
                    'id' => $destino->id,
                    'name' => $destino->name,
                    'slug' => $destino->slug
                ],
                'summary' => $summary
            ];

            return $this->successResponse($data, 'Resumen obtenido exitosamente.');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener el resumen: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/reviews/{id}/report",
     *     operationId="reportReview",
     *     tags={"Reviews"},
     *     summary="Reportar una reseña",
     *     description="Permite a un usuario reportar una reseña inapropiada",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la reseña",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="reason", type="string", enum={"inappropriate_content","spam","fake_review","harassment","offensive_language","other"}, description="Razón del reporte")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reporte creado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Reporte enviado exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     )
     * )
     */
    public function report(Request $request, int $id): JsonResponse
    {
        try {
            $review = Review::findOrFail($id);

            // Verificar que el usuario no está reportando su propia reseña
            if ($review->user_id === auth()->id()) {
                return $this->errorResponse('No puedes reportar tu propia reseña.', 422);
            }

            $request->validate([
                'reason' => 'required|string|in:' . implode(',', array_keys(ReviewReport::REASONS))
            ]);

            // Verificar si ya existe un reporte pendiente
            $existingReport = ReviewReport::where('review_id', $id)
                ->where('reporter_id', auth()->id())
                ->where('status', ReviewReport::STATUS_PENDING)
                ->first();

            if ($existingReport) {
                return $this->errorResponse('Ya has reportado esta reseña.', 422);
            }

            // Crear el reporte
            ReviewReport::create([
                'review_id' => $id,
                'reporter_id' => auth()->id(),
                'reason' => $request->reason,
                'status' => ReviewReport::STATUS_PENDING
            ]);

            return $this->successResponse(null, 'Reporte enviado exitosamente.');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al enviar el reporte: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/reviews/{id}/reply",
     *     operationId="replyToReview",
     *     tags={"Reviews"},
     *     summary="Responder a una reseña",
     *     description="Permite al propietario del destino responder a una reseña",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la reseña",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="reply", type="string", description="Respuesta a la reseña")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Respuesta agregada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Respuesta agregada exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado - No eres el propietario del destino"
     *     )
     * )
     */
    public function reply(Request $request, int $id): JsonResponse
    {
        try {
            $review = Review::with('destino')->findOrFail($id);

            // Verificar que el usuario es el propietario del destino
            if ($review->destino->user_id !== auth()->id()) {
                return $this->errorResponse('No tienes permisos para responder a esta reseña.', 403);
            }

            $request->validate([
                'reply' => 'required|string|max:1000'
            ]);

            // Agregar la respuesta (asumiendo que hay un campo reply en el modelo Review)
            $review->update([
                'reply' => $request->reply,
                'replied_at' => now()
            ]);

            return $this->successResponse(null, 'Respuesta agregada exitosamente.');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al agregar la respuesta: ' . $e->getMessage(), 500);
        }
    }
} 