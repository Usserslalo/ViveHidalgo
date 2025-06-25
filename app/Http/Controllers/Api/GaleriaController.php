<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Destino;
use App\Models\Imagen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="Gallery Management",
 *     description="Endpoints para gestión de galerías de imágenes"
 * )
 */
class GaleriaController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/v1/user/imagenes/galeria/{destino_id}",
     *     operationId="getDestinoGallery",
     *     summary="Listar galería de imágenes de un destino",
     *     description="Devuelve todas las imágenes de un destino específico, ordenadas por el campo 'orden'. Solo permite acceso a destinos del usuario autenticado.",
     *     tags={"Gallery Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="destino_id",
     *         in="path",
     *         required=true,
     *         description="ID del destino",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="destino", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="slug", type="string")
     *                 ),
     *                 @OA\Property(property="imagenes", type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="path", type="string"),
     *                         @OA\Property(property="url", type="string"),
     *                         @OA\Property(property="alt", type="string", nullable=true),
     *                         @OA\Property(property="orden", type="integer"),
     *                         @OA\Property(property="is_main", type="boolean"),
     *                         @OA\Property(property="mime_type", type="string"),
     *                         @OA\Property(property="size", type="integer"),
     *                         @OA\Property(property="created_at", type="string", format="date-time")
     *                     )
     *                 ),
     *                 @OA\Property(property="stats", type="object",
     *                     @OA\Property(property="total_imagenes", type="integer"),
     *                     @OA\Property(property="imagen_principal", type="integer", nullable=true),
     *                     @OA\Property(property="tamaño_total", type="integer"),
     *                     @OA\Property(property="tipos_archivo", type="object")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Galería recuperada con éxito.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado - El destino no pertenece al usuario"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Destino no encontrado"
     *     )
     * )
     */
    public function index(int $destino_id): JsonResponse
    {
        try {
            // Verificar que el destino pertenece al usuario autenticado
            $destino = Destino::where('id', $destino_id)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            // Obtener imágenes ordenadas
            $imagenes = Imagen::where('imageable_type', Destino::class)
                ->where('imageable_id', $destino_id)
                ->orderBy('orden', 'asc')
                ->get();

            // Calcular estadísticas
            $stats = [
                'total_imagenes' => $imagenes->count(),
                'imagen_principal' => $imagenes->where('is_main', true)->first()?->id,
                'tamaño_total' => $imagenes->sum('size'),
                'tipos_archivo' => $imagenes->groupBy('mime_type')->map->count()
            ];

            $responseData = [
                'destino' => [
                    'id' => $destino->id,
                    'name' => $destino->name,
                    'slug' => $destino->slug
                ],
                'imagenes' => $imagenes,
                'stats' => $stats
            ];

            return $this->successResponse($responseData, 'Galería recuperada con éxito.');

        } catch (\Exception $e) {
            return $this->errorResponse('Error al obtener la galería: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/user/imagenes/galeria/{destino_id}/reorder",
     *     operationId="reorderDestinoGallery",
     *     summary="Reordenar imágenes de la galería",
     *     description="Reordena las imágenes de un destino según el array de IDs proporcionado. Actualiza el campo 'orden' de cada imagen.",
     *     tags={"Gallery Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="destino_id",
     *         in="path",
     *         required=true,
     *         description="ID del destino",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="imagen_ids", type="array",
     *                 @OA\Items(type="integer"),
     *                 description="Array con los IDs de las imágenes en el nuevo orden"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Galería reordenada con éxito.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     )
     * )
     */
    public function reorder(int $destino_id, Request $request): JsonResponse
    {
        try {
            // Verificar que el destino pertenece al usuario autenticado
            $destino = Destino::where('id', $destino_id)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            // Validar request
            $request->validate([
                'imagen_ids' => 'required|array|min:1',
                'imagen_ids.*' => 'integer|exists:imagenes,id'
            ]);

            // Verificar que todas las imágenes pertenecen al destino
            $imagenesDelDestino = Imagen::where('imageable_type', Destino::class)
                ->where('imageable_id', $destino_id)
                ->pluck('id')
                ->toArray();

            $imagenesSolicitadas = $request->input('imagen_ids');
            
            if (count(array_diff($imagenesSolicitadas, $imagenesDelDestino)) > 0) {
                return $this->errorResponse('Algunas imágenes no pertenecen a este destino.', 403);
            }

            // Reordenar en transacción
            DB::transaction(function () use ($imagenesSolicitadas) {
                foreach ($imagenesSolicitadas as $orden => $imagenId) {
                    Imagen::where('id', $imagenId)->update(['orden' => $orden + 1]);
                }
            });

            return $this->successResponse(null, 'Galería reordenada con éxito.');

        } catch (\Exception $e) {
            return $this->errorResponse('Error al reordenar la galería: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/user/imagenes/galeria/{destino_id}/set-main",
     *     operationId="setMainImage",
     *     summary="Establecer imagen principal",
     *     description="Establece una imagen como principal del destino. Solo puede haber una imagen principal por destino.",
     *     tags={"Gallery Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="destino_id",
     *         in="path",
     *         required=true,
     *         description="ID del destino",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="imagen_id", type="integer", description="ID de la imagen a establecer como principal")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Imagen principal establecida con éxito.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     )
     * )
     */
    public function setMain(int $destino_id, Request $request): JsonResponse
    {
        try {
            // Verificar que el destino pertenece al usuario autenticado
            $destino = Destino::where('id', $destino_id)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            // Validar request
            $request->validate([
                'imagen_id' => 'required|integer|exists:imagenes,id'
            ]);

            $imagenId = $request->input('imagen_id');

            // Verificar que la imagen pertenece al destino
            $imagen = Imagen::where('id', $imagenId)
                ->where('imageable_type', Destino::class)
                ->where('imageable_id', $destino_id)
                ->first();

            if (!$imagen) {
                return $this->errorResponse('La imagen no pertenece a este destino.', 403);
            }

            // Establecer como principal en transacción
            DB::transaction(function () use ($destino_id, $imagenId) {
                // Quitar imagen principal actual
                Imagen::where('imageable_type', Destino::class)
                    ->where('imageable_id', $destino_id)
                    ->where('is_main', true)
                    ->update(['is_main' => false]);

                // Establecer nueva imagen principal
                Imagen::where('id', $imagenId)->update(['is_main' => true]);
            });

            return $this->successResponse(null, 'Imagen principal establecida con éxito.');

        } catch (\Exception $e) {
            return $this->errorResponse('Error al establecer imagen principal: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/user/imagenes/galeria/{destino_id}/{imagen_id}",
     *     operationId="deleteImage",
     *     summary="Eliminar imagen de la galería",
     *     description="Elimina una imagen específica de la galería del destino. Si es la imagen principal, se elimina sin reemplazo automático.",
     *     tags={"Gallery Management"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="destino_id",
     *         in="path",
     *         required=true,
     *         description="ID del destino",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="imagen_id",
     *         in="path",
     *         required=true,
     *         description="ID de la imagen a eliminar",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Operación exitosa",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Imagen eliminada con éxito.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado - La imagen no pertenece al destino del usuario"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Imagen no encontrada"
     *     )
     * )
     */
    public function destroy(int $destino_id, int $imagen_id): JsonResponse
    {
        try {
            // Verificar que el destino pertenece al usuario autenticado
            $destino = Destino::where('id', $destino_id)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            // Verificar que la imagen pertenece al destino
            $imagen = Imagen::where('id', $imagen_id)
                ->where('imageable_type', Destino::class)
                ->where('imageable_id', $destino_id)
                ->firstOrFail();

            // Verificar que no es la única imagen
            $totalImagenes = Imagen::where('imageable_type', Destino::class)
                ->where('imageable_id', $destino_id)
                ->count();

            if ($totalImagenes <= 1) {
                return $this->errorResponse('No se puede eliminar la única imagen del destino.', 422);
            }

            // Eliminar imagen en transacción
            DB::transaction(function () use ($imagen) {
                // Eliminar archivo físico si existe
                if (Storage::disk($imagen->disk)->exists($imagen->path)) {
                    Storage::disk($imagen->disk)->delete($imagen->path);
                }

                // Eliminar registro
                $imagen->delete();

                // Si era la imagen principal, establecer la primera como principal
                if ($imagen->is_main) {
                    $nuevaPrincipal = Imagen::where('imageable_type', Destino::class)
                        ->where('imageable_id', $imagen->imageable_id)
                        ->orderBy('orden', 'asc')
                        ->first();

                    if ($nuevaPrincipal) {
                        $nuevaPrincipal->update(['is_main' => true]);
                    }
                }
            });

            return $this->successResponse(null, 'Imagen eliminada con éxito.');

        } catch (\Exception $e) {
            return $this->errorResponse('Error al eliminar la imagen: ' . $e->getMessage(), 500);
        }
    }
} 