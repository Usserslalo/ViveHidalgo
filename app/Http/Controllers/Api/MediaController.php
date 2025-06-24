<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Imagen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Media",
 *     description="Endpoints para gestión de medios e imágenes"
 * )
 */
class MediaController extends BaseController
{
    /**
     * @OA\Post(
     *     path="/api/v1/media/upload",
     *     summary="Subir imagen",
     *     description="Sube una imagen y la asocia a un modelo específico",
     *     tags={"Media"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file", "imageable_type", "imageable_id"},
     *                 @OA\Property(property="file", type="string", format="binary", description="Archivo de imagen"),
     *                 @OA\Property(property="imageable_type", type="string", description="Tipo de modelo (App\\Models\\Destino, App\\Models\\Promocion, etc.)"),
     *                 @OA\Property(property="imageable_id", type="integer", description="ID del modelo"),
     *                 @OA\Property(property="alt", type="string", description="Texto alternativo de la imagen"),
     *                 @OA\Property(property="is_main", type="boolean", description="Indica si es la imagen principal"),
     *                 @OA\Property(property="orden", type="integer", description="Orden de la imagen")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Imagen subida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Imagen subida exitosamente"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/Imagen"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error de validación"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No autorizado")
     *         )
     *     )
     * )
     */
    public function upload(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:jpg,jpeg,png,webp|max:5120', // 5MB máximo
            'imageable_type' => 'required|string|in:App\Models\Destino,App\Models\Promocion,App\Models\Region',
            'imageable_id' => 'required|integer',
            'alt' => 'nullable|string|max:255',
            'is_main' => 'boolean',
            'orden' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Error de validación', 400, $validator->errors());
        }

        // Verificar que el modelo existe
        $modelClass = $request->input('imageable_type');
        $modelId = $request->input('imageable_id');
        
        $model = $modelClass::find($modelId);
        if (!$model) {
            return $this->errorResponse('Modelo no encontrado', 404);
        }

        // Verificar permisos (solo el dueño o admin puede subir imágenes)
        if (!$this->canUploadImage($model)) {
            return $this->errorResponse('No autorizado para subir imágenes a este recurso', 403);
        }

        try {
            $file = $request->file('file');
            $filename = $this->generateFilename($file);
            $path = $this->storeImage($file, $filename);

            // Crear registro de imagen
            $imagen = Imagen::create([
                'imageable_type' => $modelClass,
                'imageable_id' => $modelId,
                'path' => $path,
                'alt' => $request->input('alt'),
                'is_main' => $request->boolean('is_main', false),
                'orden' => $request->input('orden', 0),
                'disk' => 'public',
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);

            return $this->successResponse($imagen, 'Imagen subida exitosamente', 201);

        } catch (\Exception $e) {
            return $this->errorResponse('Error al subir la imagen: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/media/{imagen}",
     *     summary="Eliminar imagen",
     *     description="Elimina una imagen específica",
     *     tags={"Media"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="imagen",
     *         in="path",
     *         required=true,
     *         description="ID de la imagen",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Imagen eliminada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Imagen eliminada exitosamente")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Imagen no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Imagen no encontrada")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="No autorizado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No autorizado")
     *         )
     *     )
     * )
     */
    public function destroy(Imagen $imagen): JsonResponse
    {
        // Verificar permisos
        if (!$this->canDeleteImage($imagen)) {
            return $this->errorResponse('No autorizado para eliminar esta imagen', 403);
        }

        try {
            $imagen->delete();
            return $this->successResponse(null, 'Imagen eliminada exitosamente');
        } catch (\Exception $e) {
            return $this->errorResponse('Error al eliminar la imagen: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Generar nombre único para el archivo
     */
    private function generateFilename($file): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = Str::random(8);
        
        return "images/{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Almacenar y optimizar la imagen
     */
    private function storeImage($file, $filename): string
    {
        // Crear directorio si no existe
        $directory = dirname($filename);
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        // Optimizar imagen si es posible
        if (class_exists('Intervention\Image\Facades\Image')) {
            $image = Image::make($file);
            
            // Redimensionar si es muy grande (máximo 1920px de ancho)
            if ($image->width() > 1920) {
                $image->resize(1920, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }
            
            // Guardar imagen optimizada
            $image->save(storage_path("app/public/{$filename}"), 85);
        } else {
            // Sin optimización, guardar directamente
            Storage::disk('public')->putFileAs(
                dirname($filename),
                $file,
                basename($filename)
            );
        }

        return $filename;
    }

    /**
     * Verificar si el usuario puede subir imágenes al modelo
     */
    private function canUploadImage($model): bool
    {
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        // Admin puede subir a cualquier modelo
        if ($user->hasRole('admin')) {
            return true;
        }

        // Verificar si el usuario es dueño del modelo
        if (method_exists($model, 'user_id') && $model->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Verificar si el usuario puede eliminar la imagen
     */
    private function canDeleteImage(Imagen $imagen): bool
    {
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        // Admin puede eliminar cualquier imagen
        if ($user->hasRole('admin')) {
            return true;
        }

        // Verificar si el usuario es dueño del modelo asociado
        $model = $imagen->imageable;
        if ($model && method_exists($model, 'user_id') && $model->user_id === $user->id) {
            return true;
        }

        return false;
    }
} 